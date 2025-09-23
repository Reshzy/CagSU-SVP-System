<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id',
        'employee_id',
        'position',
        'phone',
        'is_active',
        'approval_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    /**
     * Get the department that owns the user.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get purchase requests created by this user.
     */
    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class, 'requester_id');
    }

    /**
     * Get workflow approvals assigned to this user.
     */
    public function approvals()
    {
        return $this->hasMany(WorkflowApproval::class, 'approver_id');
    }

    /**
     * Polymorphic documents attached to the user (e.g., ID proofs).
     */
    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Check if user has specific role for CagSU SVP system.
     */
    public function isSVPRole($role)
    {
        return $this->hasRole($role);
    }

    /**
     * Get user's primary SVP role for dashboard customization.
     */
    public function getPrimarySVPRole()
    {
        $roles = $this->getRoleNames();
        
        // Priority order for primary role determination
        $rolePriority = [
            'System Admin',
            'Executive Officer', 
            'Supply Officer',
            'BAC Chair',
            'Budget Office',
            'BAC Members',
            'BAC Secretariat',
            'Canvassing Unit',
            'Accounting Office',
            'End User',
            'Supplier'
        ];
        
        foreach ($rolePriority as $role) {
            if ($roles->contains($role)) {
                return $role;
            }
        }
        
        return 'End User'; // Default role
    }
}
