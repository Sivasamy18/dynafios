<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\MailTrackerController;
use App\Http\Controllers\Admin\Payments\AdminPaymentsController;
use App\Http\Controllers\AuditsController;
use App\Http\Controllers\Files\FileController;
use App\Http\Controllers\Admin\RolePermissionController ;

Route::resource('audits', AuditsController::class)->only('index');
// File Routes
Route::get('/file/{fileId}/download', [FileController::class, 'download']);

Route::group(['middleware' => ['auth', 'dynafios-staff']], function () {
    //Maintenance Dashboards
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard.index');
    Route::get('/admin/dashboard/email-activity', [MailTrackerController::class, 'index'])->name('admin.emails.index');
    Route::get('/admin/dashboard/payments', [AdminPaymentsController::class, 'index'])->name('admin.payments.index');

    //Payment Manager
    Route::delete('/admin/dashboard/payment/{payment}/delete', [AdminPaymentsController::class, 'deletePayment'])->name('admin.payments.delete');

    //Role Permission Manager
    Route::get('/admin/dashboard/roles-permissions', [RolePermissionController::class, 'index'])->name('roles_permissions.index');
    
    //Role CRUD operation
    Route::put('/admin/dashboard/roles-permissions/role/{id}', [RolePermissionController::class, 'updateRole'])->name('roles_permissions.updateRole');
    Route::post('/admin/dashboard/create/role', [RolePermissionController::class, 'createRole'])->name('roles_permissions.createRole');
    Route::delete('/admin/dashboard/delete/role/{id}', [RolePermissionController::class, 'deleteRole'])->name('roles_permissions.deleteRole');
    Route::get('/admin/dashboard/edit/role/{id}',[RolePermissionController::class,'editRole'])->name('role_permission.editRole');
    Route::get('/admin/dashboard/roles',[RolePermissionController::class,'viewRoles'])->name('roles_permissions.viewRoles');
    
   //Permission CRUD operation
    Route::post('/admin/dashboard/roles-permissions', [RolePermissionController::class, 'createPermission'])->name('roles_permissions.createPermission');
    Route::delete('/admin/dashboard/delete/permission/{id}', [RolePermissionController::class, 'deletePermission'])->name('roles_permissions.deletePermission');
    Route::get('/admin/dashboard/edit/permission/{id}',[RolePermissionController::class,'editPermission'])->name('role_permission.editPermission');
    Route::put('/admin/dashboard/roles-permissions/permission/{id}', [RolePermissionController::class, 'updatePermission'])->name('roles_permissions.updatePermission');
    Route::get('/admin/dashboard/permissions',[RolePermissionController::class,'viewPermission'])->name('roles_permissions.viewPermission');

    Route::get('/admin/dashboard/roles-permissions/users',[RolePermissionController::class,'viewUsers'])->name('role_permission.viewUsers');
    Route::get('/admin/dashboard/roles-permissions/role/{search}',[RolePermissionController::class, 'searchRole'])->name('role_permission.searchRole');
    Route::get('/admin/dashboard/roles-permissions/permission/{search}',[RolePermissionController::class, 'searchPermission'])->name('role_permission.searchPermission');
    Route::get('/admin/dashboard/roles-permissions/user/{search}',[RolePermissionController::class, 'searchUser'])->name('role_permission.searchUser');

    //user Role edit
    Route::get('/admin/dashboard/edit/user/{id}',[RolePermissionController::class,'editUserRole'])->name('role_permission.editUserRole');
    Route::put('/admin/dashboard/roles-permissions/user/{id}', [RolePermissionController::class, 'updateUserRole'])->name('roles_permissions.updateUserRole');
});