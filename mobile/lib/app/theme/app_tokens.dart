import 'package:flutter/material.dart';

abstract final class AppColors {
  static const primary = Color(0xff1e88e5);
  static const primaryDark = Color(0xff1565c0);
  static const secondary = Color(0xff43a047);
  static const accent = Color(0xfffb8c00);
  static const danger = Color(0xffe53935);
  static const background = Color(0xfff5f7fa);
  static const surface = Colors.white;
  static const text = Color(0xff243447);
  static const muted = Color(0xff64748b);
  static const border = Color(0xffdce3ea);
}

abstract final class AppSpacing {
  static const xxs = 4.0;
  static const xs = 8.0;
  static const sm = 12.0;
  static const md = 16.0;
  static const lg = 24.0;
  static const xl = 32.0;
  static const xxl = 40.0;
}

abstract final class AppRadius {
  static const control = 8.0;
  static const card = 12.0;
}
