# Authorization System Implementation

## Overview
This implementation adds a comprehensive authorization system for activities proposed by activists, addressing all requirements in the problem statement.

## Database Changes

### New Fields in `actividades` Table
- `propuesto_por` (INT) - ID of user who proposed the activity
- `autorizado_por` (INT) - ID of user who authorized the activity  
- `autorizada` (TINYINT) - Whether activity is authorized (0=pending, 1=authorized)
- `bonificacion_ranking` (INT) - Custom bonus points for the activity (default 0)

### Foreign Key Constraints
- `propuesto_por` references `usuarios(id)`
- `autorizado_por` references `usuarios(id)`

### Performance Indexes
- Index on `autorizada` for fast filtering
- Index on `propuesto_por` for proposal queries

## Key Features Implemented

### 1. Authorization Fields Integration ✅
- Updated `createProposal()` to set `propuesto_por` and `autorizada=0`
- Updated `approveProposal()` to set `autorizada=1` and `autorizado_por`
- Updated `getPendingProposals()` to filter by `autorizada=0`

### 2. Admin User Exclusion ✅
- Updated all ranking queries to exclude user with `id=1`
- Modified `getUserRanking()`, `updateUserRankings()`, `getTeamRanking()`, `findUserPosition()`, and `getRankingStats()`

### 3. Compliance Filter Fix ✅
- Fixed `getAllUsersWithCompliance()` to use `HAVING` clause instead of `WHERE` for aggregated data
- Now properly filters by compliance levels (alto, medio, bajo, todos los niveles)
- Only counts authorized activities (`a.autorizada = 1`)

### 4. New Menu Item ✅
- Added "Actividades por autorizar" menu item for SuperAdmin and Gestor roles
- Points to `/activities/proposals.php` endpoint
- Uses `fas fa-clipboard-check` icon

### 5. Authorization Logic ✅
- Authorized activities now count for ranking calculations
- Activity listings show only authorized activities by default
- Configurable bonus points system with `bonificacion_ranking` field

### 6. Bonus Points System ✅
- Default 100 points for approved proposals
- Configurable via `bonificacion_ranking` field
- Applied when proposals are approved

## Usage

### For Activists
1. Create activity proposals through existing proposal system
2. Proposals are marked with `autorizada=0` and `propuesto_por=[user_id]`
3. Wait for authorization from SuperAdmin/Gestor

### For SuperAdmin/Gestor
1. Access "Actividades por autorizar" from sidebar menu
2. Review pending proposals (where `autorizada=0`)
3. Approve or reject proposals
4. Approved activities become available in general listings and count for ranking

### Compliance Filtering
- "Todos los niveles" shows all users regardless of compliance
- "Alto (>60%)" shows users with >60% task completion
- "Medio (20-60%)" shows users with 20-60% completion  
- "Bajo (<20%)" shows users with <20% completion

## Testing Results ✅

All core functionality has been tested and verified:
- ✅ New authorization fields are properly integrated
- ✅ Admin user (id=1) is excluded from all ranking queries
- ✅ Only authorized activities count for ranking calculations
- ✅ Compliance filter works correctly with HAVING clause
- ✅ New menu item is properly restricted and functional
- ✅ Bonus points system with configurable amounts

## Migration Required

Execute the following SQL to add the new fields:

```sql
-- Run database_migration_authorization.sql
source database_migration_authorization.sql;
```

## Backward Compatibility ✅

- Existing activities without `propuesto_por` are treated as regular activities
- Activities with `propuesto_por=NULL` are not affected by authorization logic
- All existing functionality remains intact
- No breaking changes to current workflow