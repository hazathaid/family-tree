<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AuditObserver
{
    private const EXCLUDED_ATTRIBUTES = ['created_at', 'updated_at', 'deleted_at'];

    private const IDENTIFIERS = ['uuid', 'full_name', 'name', 'title', 'role', 'status', 'email', 'family_id', 'source_member_id', 'target_member_id', 'user_id'];

    public function __construct(private readonly AuditLogService $auditLogs) {}

    public function created(Model $model): void
    {
        $this->write('created', $model, [], $this->summary($model));
    }

    public function updated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), self::EXCLUDED_ATTRIBUTES);

        if ($changes === []) {
            return;
        }

        $this->write('updated', $model, Arr::only($model->getOriginal(), array_keys($changes)), $changes);
    }

    public function deleted(Model $model): void
    {
        $this->write('deleted', $model, $this->summary($model), []);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Model $model): array
    {
        return Arr::only($model->getAttributes(), self::IDENTIFIERS);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function write(string $action, Model $model, array $oldValues, array $newValues): void
    {
        $actor = $this->actor();

        if ($actor === null && app()->runningInConsole()) {
            return;
        }

        $this->auditLogs->record(
            $actor,
            Str::snake(class_basename($model)).'.'.$action,
            $model,
            $oldValues,
            $newValues,
        );
    }

    private function actor(): ?User
    {
        foreach (['sanctum', 'web'] as $guard) {
            $user = auth()->guard($guard)->user();

            if ($user instanceof User) {
                return $user;
            }
        }

        return null;
    }
}
