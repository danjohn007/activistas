# User Delete and Reactivate - Visual Flow

## User State Diagram

```
┌─────────────┐
│  Pendiente  │ ◄─── New user registration
└──────┬──────┘
       │ Approved by SuperAdmin/Gestor
       ▼
┌─────────────┐
│   Activo    │ ◄──────────────────────────┐
└──────┬──────┘                            │
       │                                   │
       ├─────► Suspender ──► ┌───────────┐│
       │                     │Suspendido ││ Reactivate
       │                     └─────┬─────┘│ (SuperAdmin)
       │                           │      │
       ├─────► Desactivar ─► ┌───────────┐│
       │                     │Desactivado││
       │                     └─────┬─────┘│
       │                           │      │
       └─────► Eliminar ────► ┌───────────┐│
         (SuperAdmin only)    │ Eliminado ││
                             └─────┬─────┘│
                                   │      │
                                   └──────┘
                            All can be reactivated
                              by SuperAdmin
```

## Button Actions by User State

### Active User
```
┌────────────────────────────────────────────────────┐
│ [✏️ Edit] [🔑 Password] [⏸️ Suspend] [🚫 Deactivate] [🗑️ Delete] │
└────────────────────────────────────────────────────┘
```

### Suspended User
```
┌───────────────────────────────────────────────────┐
│ [✏️ Edit] [🔑 Password] [▶️ Activate] [🚫 Deactivate] [🗑️ Delete] │
└───────────────────────────────────────────────────┘
```

### Deactivated User
```
┌────────────────────────┐
│ [✏️ Edit] [🔄 Reactivate] │
└────────────────────────┘
```

### Eliminated User
```
┌────────────────────────┐
│ [✏️ Edit] [🔄 Reactivate] │
└────────────────────────┘
```

## API Flow - Delete User

```
┌────────────┐
│   Client   │
│  (Browser) │
└─────┬──────┘
      │ deleteUser(userId, userName)
      │ Confirmation dialog
      ▼
┌────────────────────────────────────┐
│   JavaScript Function              │
│   - Show loading state             │
│   - POST to /api/users.php         │
│   - Action: 'delete'               │
└──────────────┬─────────────────────┘
               │ JSON Request
               ▼
┌────────────────────────────────────┐
│   API Endpoint                     │
│   /public/api/users.php            │
│   - Verify SuperAdmin permission   │
│   - Get user info                  │
│   - Update status to 'eliminado'   │
│   - Log activity                   │
└──────────────┬─────────────────────┘
               │ JSON Response
               ▼
┌────────────────────────────────────┐
│   User Model                       │
│   updateUserStatus()               │
│   - Validate status                │
│   - Execute SQL UPDATE             │
│   - Return success/failure         │
└────────────────────────────────────┘
               │
               ▼
         Success/Error
               │
               ▼
┌────────────────────────────────────┐
│   Client Response                  │
│   - Show success/error message     │
│   - Reload page after 2 seconds    │
└────────────────────────────────────┘
```

## API Flow - Reactivate User

```
┌────────────┐
│   Client   │
│  (Browser) │
└─────┬──────┘
      │ reactivateUser(userId, userName)
      │ Confirmation dialog
      ▼
┌────────────────────────────────────┐
│   JavaScript Function              │
│   - Show loading state             │
│   - POST to /api/users.php         │
│   - Action: 'reactivate'           │
└──────────────┬─────────────────────┘
               │ JSON Request
               ▼
┌────────────────────────────────────┐
│   API Endpoint                     │
│   /public/api/users.php            │
│   - Verify SuperAdmin permission   │
│   - Get user info                  │
│   - Validate current state         │
│     (eliminado/desactivado/        │
│      suspendido)                   │
│   - Update status to 'activo'      │
│   - Log activity with prev state   │
└──────────────┬─────────────────────┘
               │ JSON Response
               ▼
┌────────────────────────────────────┐
│   User Model                       │
│   updateUserStatus()               │
│   - Validate status                │
│   - Execute SQL UPDATE             │
│   - Return success/failure         │
└────────────────────────────────────┘
               │
               ▼
         Success/Error
               │
               ▼
┌────────────────────────────────────┐
│   Client Response                  │
│   - Show success/error message     │
│   - Reload page after 2 seconds    │
└────────────────────────────────────┘
```

## Security Layers

```
┌──────────────────────────────────────────┐
│  Frontend Permission Check               │
│  - Buttons only shown to SuperAdmin      │
│  - Conditional rendering in PHP          │
└──────────────┬───────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────┐
│  JavaScript Confirmation                 │
│  - User must confirm action              │
│  - Clear message about consequences      │
└──────────────┬───────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────┐
│  API Permission Validation               │
│  - Verify user is logged in              │
│  - Verify user role is SuperAdmin        │
│  - Throw exception if not authorized     │
└──────────────┬───────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────┐
│  Business Logic Validation               │
│  - Verify target user exists             │
│  - Verify state is valid for operation   │
│  - Log all actions for audit             │
└──────────────┬───────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────┐
│  Database Validation                     │
│  - Enum constraint on estado field       │
│  - Only allowed values can be set        │
└──────────────────────────────────────────┘
```

## Badge Color Mapping

| Estado       | Badge Color | Bootstrap Class | Visual Effect |
|--------------|-------------|-----------------|---------------|
| Activo       | Green       | `bg-success`    | ✅ Active     |
| Pendiente    | Yellow      | `bg-warning`    | ⏳ Waiting   |
| Suspendido   | Red         | `bg-danger`     | ⏸️ Paused    |
| Desactivado  | Gray        | `bg-secondary`  | 🚫 Blocked   |
| Eliminado    | Black       | `bg-dark`       | 🗑️ Deleted   |

## Permission Matrix

| Action           | SuperAdmin | Gestor | Líder | Activista |
|------------------|------------|--------|-------|-----------|
| View users       | ✅         | ✅     | ❌    | ❌        |
| Edit user        | ✅         | ✅     | ❌    | ❌        |
| Suspend user     | ✅         | ✅     | ❌    | ❌        |
| Activate user    | ✅         | ✅     | ❌    | ❌        |
| Deactivate user  | ✅         | ✅     | ❌    | ❌        |
| **Delete user**  | **✅**     | **❌** | **❌** | **❌**   |
| **Reactivate**   | **✅**     | **❌** | **❌** | **❌**   |
| Change password  | ✅         | ❌     | ❌    | ❌        |

## Files Modified

```
activistas/
├── views/admin/users.php
│   ├── Added reactivate button
│   ├── Updated badge colors
│   ├── Added reactivateUser() JS function
│   └── Updated deleteUser() confirmation
│
├── public/api/users.php
│   ├── Added 'reactivate' case
│   ├── Permission validation
│   ├── State validation
│   └── Activity logging
│
└── USER_DELETE_REACTIVATE_IMPLEMENTATION.md
    └── Complete documentation
```

## Testing Checklist

- [ ] SuperAdmin can see delete button for non-eliminated users
- [ ] SuperAdmin can see reactivate button for eliminated/deactivated users
- [ ] Delete button shows confirmation dialog
- [ ] Reactivate button shows confirmation dialog
- [ ] Delete action sets status to 'eliminado'
- [ ] Reactivate action sets status to 'activo'
- [ ] Gestor cannot see delete or reactivate buttons
- [ ] Badge shows correct color for 'eliminado' status
- [ ] Actions are logged in activity log
- [ ] Page reloads after successful action
- [ ] Error messages display correctly
- [ ] Loading states work correctly
- [ ] Eliminated users appear in filter
- [ ] Eliminated users cannot login
- [ ] Reactivated users can login

## Example Usage

### Deleting a User
1. Navigate to Gestión de Usuarios as SuperAdmin
2. Find user in the list
3. Click the red trash icon (🗑️)
4. Confirm the action in dialog
5. User status changes to 'Eliminado'
6. User appears with black badge
7. Action logged in system

### Reactivating a User
1. Navigate to Gestión de Usuarios as SuperAdmin
2. Filter by 'Eliminado' or find deactivated user
3. Click the green reload icon (🔄)
4. Confirm the action in dialog
5. User status changes to 'Activo'
6. User appears with green badge
7. Action logged with previous state
8. User can now login again
