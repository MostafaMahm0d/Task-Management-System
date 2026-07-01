<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['project_id', 'user_id', 'role'])]
class ProjectMember extends Pivot
{
    public const ROLE_OWNER = 'owner';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_MEMBER = 'member';

    public $incrementing = true;

    protected $table = 'project_members';
}
