<?php

namespace App\Http\Resources;

use App\Models\MemberAccountInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class MemberAccountInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof MemberAccountInvitation) {
            throw new LogicException('MemberAccountInvitationResource requires a member invitation.');
        }

        $invitation = $this->resource;

        return [
            'uuid' => $invitation->uuid,
            'email' => $invitation->email,
            'family_member_uuid' => $invitation->member->uuid,
            'family_member_name' => $invitation->member->full_name,
            'expires_at' => $invitation->expires_at->toISOString(),
            'accepted_at' => $invitation->accepted_at?->toISOString(),
        ];
    }
}
