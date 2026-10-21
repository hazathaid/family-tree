#!/usr/bin/env python3
"""Enforce a minimum Flutter line-coverage threshold from an lcov file.

Usage:
    flutter test --coverage
    python3 scripts/check-flutter-coverage.py --min 80

Exits non-zero when the measured coverage is below the threshold. Generated
localization files can be excluded with --exclude-prefix.
"""

from __future__ import annotations

import argparse
import sys
from pathlib import Path


def parse_lcov(path: Path) -> tuple[int, int]:
    found = hit = 0
    current_hit: int | None = None
    current_found: int | None = None

    for raw in path.read_text(encoding="utf-8").splitlines():
        if raw.startswith("SF:"):
            current_hit = current_found = None
        elif raw.startswith("LF:"):
            current_found = int(raw[3:])
        elif raw.startswith("LH:"):
            current_hit = int(raw[3:])
        elif raw.startswith("end_of_record"):
            if current_found is not None and current_hit is not None:
                found += current_found
                hit += current_hit
            current_hit = current_found = None

    return hit, found


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--lcov", default="coverage/lcov.info")
    parser.add_argument("--min", type=float, default=80.0)
    parser.add_argument("--exclude-prefix", action="append", default=[])
    args = parser.parse_args()

    path = Path(args.lcov)
    if not path.exists():
        print(f"lcov file not found: {path}", file=sys.stderr)
        return 2

    # When exclusions are requested, rebuild totals by filtering file records.
    if args.exclude_prefix:
        hit = found = 0
        current_hit = current_found = None
        current_file = ""
        for raw in path.read_text(encoding="utf-8").splitlines():
            if raw.startswith("SF:"):
                current_file = raw[3:]
                current_hit = current_found = None
            elif raw.startswith("LF:"):
                current_found = int(raw[3:])
            elif raw.startswith("LH:"):
                current_hit = int(raw[3:])
            elif raw.startswith("end_of_record"):
                excluded = any(current_file.startswith(prefix) for prefix in args.exclude_prefix)
                if not excluded and current_found is not None and current_hit is not None:
                    found += current_found
                    hit += current_hit
                current_hit = current_found = None
    else:
        hit, found = parse_lcov(path)

    if found == 0:
        print("lcov file contains no line records", file=sys.stderr)
        return 2

    coverage = hit / found * 100
    print(f"Flutter line coverage: {coverage:.2f}% ({hit}/{found}), required {args.min:.2f}%")

    if coverage + 1e-9 < args.min:
        print("Coverage gate failed.", file=sys.stderr)
        return 1

    print("Coverage gate passed.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
