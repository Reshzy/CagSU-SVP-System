# Fixes: Signatory Access & Manual Entry Support

## Date: November 16, 2025

## Issues Fixed

### Issue 1: No Dashboard Access to Signatory Management
**Problem:** Authorized users (System Admin and BAC Chair) had no way to access `/bac/signatories` from their dashboard.

**Solution:** Added a "Management Tools" section to the BAC dashboard with quick action buttons.

### Issue 2: No Manual Entry for Non-Account Holders
**Problem:** There was no way to add BAC members who don't have user accounts in the system yet.

**Solution:** Enhanced the signatory system to support both user account selection and manual name entry.

---

## Changes Made

### 1. Dashboard Access (`resources/views/dashboard/bac.blade.php`)

Added a new "Management Tools" section visible only to System Admin and BAC Chair:

```blade
@if(auth()->user()->hasAnyRole(['System Admin', 'BAC Chair']))
<div class="bg-white shadow rounded-lg mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Management Tools</h3>
    </div>
    <div class="px-6 py-4">
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('bac.signatories.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-cagsu-blue hover:bg-blue-800 text-white text-sm font-medium rounded-md shadow-sm transition-colors">
                <svg class="w-5 h-5 mr-2">...</svg>
                Manage BAC Signatories
            </a>
            <a href="{{ route('bac.meetings.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-cagsu-orange hover:bg-orange-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors">
                <svg class="w-5 h-5 mr-2">...</svg>
                Schedule BAC Meeting
            </a>
        </div>
    </div>
</div>
@endif
```

**Benefits:**
- Easy access from the dashboard
- Only visible to authorized users
- Includes other management tools for convenience

---

### 2. Database Migration (`database/migrations/2025_11_16_131818_make_user_id_nullable_in_bac_signatories_table.php`)

**Changes:**
- Made `user_id` column nullable
- Added `manual_name` column (varchar 255, nullable)
- Removed unique constraint on `user_id` and `position` combination
- Maintained foreign key constraint with cascade delete

**Schema After Migration:**
```sql
CREATE TABLE bac_signatories (
    id BIGINT UNSIGNED PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,              -- Now nullable
    manual_name VARCHAR(255) NULL,             -- NEW FIELD
    position ENUM(...),
    prefix VARCHAR(50) NULL,
    suffix VARCHAR(50) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

### 3. Model Updates (`app/Models/BacSignatory.php`)

**Added Fields:**
```php
protected $fillable = [
    'user_id',
    'manual_name',      // NEW
    'position',
    'prefix',
    'suffix',
    'is_active',
];
```

**New Method:**
```php
public function getDisplayNameAttribute(): string
{
    return $this->manual_name ?? ($this->user ? $this->user->name : 'N/A');
}
```

**Updated Method:**
```php
public function getFullNameAttribute(): string
{
    $name = $this->display_name;  // Uses manual_name or user name
    
    if ($this->prefix) {
        $name = $this->prefix . ' ' . $name;
    }
    
    if ($this->suffix) {
        $name .= ', ' . $this->suffix;
    }
    
    return $name;
}
```

---

### 4. Controller Updates (`app/Http/Controllers/BacSignatoryController.php`)

**New Validation Rules:**
```php
$validated = $request->validate([
    'input_type' => ['required', 'in:user,manual'],
    'user_id' => ['nullable', 'required_if:input_type,user', 'exists:users,id'],
    'manual_name' => ['nullable', 'required_if:input_type,manual', 'string', 'max:255'],
    'position' => ['required', 'in:bac_chairman,bac_vice_chairman,bac_member,head_bac_secretariat,ceo'],
    'prefix' => ['nullable', 'string', 'max:50'],
    'suffix' => ['nullable', 'string', 'max:50'],
    'is_active' => ['boolean'],
]);
```

**Store Logic:**
```php
BacSignatory::create([
    'user_id' => $validated['input_type'] === 'user' ? $validated['user_id'] : null,
    'manual_name' => $validated['input_type'] === 'manual' ? $validated['manual_name'] : null,
    'position' => $validated['position'],
    'prefix' => $validated['prefix'] ?? null,
    'suffix' => $validated['suffix'] ?? null,
    'is_active' => $validated['is_active'] ?? true,
]);
```

---

### 5. View Updates

#### Index View (`resources/views/bac/signatories/index.blade.php`)

**Updated Display:**
```blade
<td class="px-6 py-4 whitespace-nowrap">
    <div class="text-sm font-medium text-gray-900">{{ $signatory->display_name }}</div>
    @if($signatory->user)
        <div class="text-sm text-gray-500">{{ $signatory->user->email }}</div>
    @else
        <div class="text-sm text-gray-500 italic">Manual Entry</div>
    @endif
</td>
```

#### Create/Edit Forms

**Added Entry Type Selection:**
```blade
<!-- Input Type Selection -->
<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Entry Type <span class="text-red-500">*</span></label>
    <div class="flex space-x-4">
        <label class="inline-flex items-center">
            <input type="radio" name="input_type" value="user" checked 
                   class="form-radio text-cagsu-blue" onchange="toggleInputType('user')">
            <span class="ml-2">Select from User Accounts</span>
        </label>
        <label class="inline-flex items-center">
            <input type="radio" name="input_type" value="manual" 
                   class="form-radio text-cagsu-blue" onchange="toggleInputType('manual')">
            <span class="ml-2">Manual Entry</span>
        </label>
    </div>
</div>

<!-- User Selection -->
<div id="user-section">
    <label>User</label>
    <select name="user_id">
        <!-- User options -->
    </select>
</div>

<!-- Manual Name Entry -->
<div id="manual-section" class="hidden">
    <label>Full Name</label>
    <input type="text" name="manual_name" placeholder="Enter full name">
    <p class="text-xs text-gray-500">For BAC members who don't have user accounts yet</p>
</div>
```

**JavaScript Toggle Function:**
```javascript
function toggleInputType(type) {
    const userSection = document.getElementById('user-section');
    const manualSection = document.getElementById('manual-section');
    
    if (type === 'user') {
        userSection.classList.remove('hidden');
        manualSection.classList.add('hidden');
        document.getElementById('manual_name').value = '';
    } else {
        userSection.classList.add('hidden');
        manualSection.classList.remove('hidden');
        document.getElementById('user_id').value = '';
    }
}
```

---

## How It Works

### Creating a User-Based Signatory
1. Navigate to Dashboard → "Manage BAC Signatories"
2. Click "Add New Signatory"
3. Select "Select from User Accounts"
4. Choose user from dropdown
5. Select position
6. Add prefix/suffix if needed
7. Save

### Creating a Manual Entry Signatory
1. Navigate to Dashboard → "Manage BAC Signatories"
2. Click "Add New Signatory"
3. Select "Manual Entry"
4. Type full name (e.g., "Juan Dela Cruz")
5. Select position
6. Add prefix (e.g., "Dr.") and suffix (e.g., "Ph.D.") if needed
7. Save

### Display Behavior
- **User-based signatories:** Show user's name and email
- **Manual entries:** Show name and "Manual Entry" label
- **Both types:** Display with prefix/suffix in resolution documents

---

## Benefits

### 1. Dashboard Access
✅ Easy access for authorized users  
✅ No need to remember URLs  
✅ Centralized management location  
✅ Clear visual button with icon  

### 2. Manual Entry Support
✅ Can add BAC members without user accounts  
✅ Flexible for temporary or external members  
✅ Same prefix/suffix functionality  
✅ Clear indication in the list (shows "Manual Entry")  
✅ No database constraint violations  

### 3. User Experience
✅ Simple radio toggle between modes  
✅ Clear labels and instructions  
✅ Validation prevents submission errors  
✅ Maintains existing functionality  

---

## Testing Checklist

- [x] Dashboard button appears for System Admin
- [x] Dashboard button appears for BAC Chair
- [x] Dashboard button hidden for other roles
- [x] Can create signatory with user account
- [x] Can create signatory with manual name
- [x] Prefix and suffix work for both types
- [x] Index page displays both types correctly
- [x] Can edit user-based signatory
- [x] Can edit manual signatory
- [x] Can switch between modes when editing
- [ ] Resolutions generate correctly with manual entries
- [ ] Dropdown in resolution form includes manual entries

---

## Files Modified

1. ✅ `resources/views/dashboard/bac.blade.php` - Added management tools section
2. ✅ `database/migrations/2025_11_16_131818_make_user_id_nullable_in_bac_signatories_table.php` - New migration
3. ✅ `app/Models/BacSignatory.php` - Added manual_name support
4. ✅ `app/Http/Controllers/BacSignatoryController.php` - Updated validation and logic
5. ✅ `resources/views/bac/signatories/index.blade.php` - Updated display logic
6. ✅ `resources/views/bac/signatories/create.blade.php` - Added manual entry option
7. ✅ `resources/views/bac/signatories/edit.blade.php` - Added manual entry option

---

## Migration Status

✅ Migration completed successfully  
✅ `user_id` is now nullable  
✅ `manual_name` column added  
✅ Foreign key constraint maintained  
✅ No linter errors  

---

## Next Steps (Optional Enhancements)

1. Add validation to prevent duplicate manual names in the same position
2. Add search/filter functionality in signatory index
3. Add bulk import for manual signatories
4. Add signatory history/audit log
5. Add notification when signatories are added/changed

