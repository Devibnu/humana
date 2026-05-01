<?php

use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendancesAnalyticsController;
use App\Http\Controllers\AttendancesAnalyticsExportController;
use App\Http\Controllers\AttendancesDashboardController;
use App\Http\Controllers\AttendancesExportController;
use App\Http\Controllers\BankAccountsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FamilyMembersController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JenisCutiController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LemburController;
use App\Http\Controllers\LeavesExportController;
use App\Http\Controllers\LeavesAnalyticsController;
use App\Http\Controllers\LeavesAggregationExportController;
use App\Http\Controllers\LeavesAnomalyController;
use App\Http\Controllers\LeavesAnomalyExportController;
use App\Http\Controllers\LeavesAnomalyResolutionController;
use App\Http\Controllers\LeavesAnomalyResolutionExportController;
use App\Http\Controllers\OwnerTenantController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollReportController;
use App\Http\Controllers\AbsenceRuleController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\PositionsExportController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\ResetController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkLocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::group(['middleware' => 'auth'], function () {
	Route::get('owner/tenants', [OwnerTenantController::class, 'index'])->name('owner.tenants.index');
	Route::post('owner/tenants', [OwnerTenantController::class, 'store'])->name('owner.tenants.store');
	Route::put('owner/tenants/{tenant}', [OwnerTenantController::class, 'update'])->name('owner.tenants.update');
	Route::delete('owner/tenants/{tenant}', [OwnerTenantController::class, 'destroy'])->name('owner.tenants.destroy');

    Route::get('/', [HomeController::class, 'home']);
	Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

	Route::get('billing', function () {
		return view('billing');
	})->name('billing');

	Route::get('payroll', [PayrollController::class, 'index'])->middleware('menu_access:payroll')->name('payroll.index');
	Route::get('payroll/reports', [PayrollReportController::class, 'index'])->middleware('menu_access:payroll.reports')->name('payroll.reports');
	Route::get('payroll/reports/export/{format}', [PayrollReportController::class, 'export'])->middleware('menu_access:payroll.reports')->whereIn('format', ['xlsx', 'pdf'])->name('payroll.reports.export');
	Route::get('payroll/create', [PayrollController::class, 'create'])->middleware('menu_access:payroll')->name('payroll.create');
	Route::post('payroll', [PayrollController::class, 'store'])->middleware('menu_access:payroll')->name('payroll.store');
	Route::get('payroll/{payroll}', [PayrollController::class, 'show'])->middleware('menu_access:payroll')->name('payroll.show');
	Route::get('payroll/{payroll}/edit', [PayrollController::class, 'edit'])->middleware('menu_access:payroll')->name('payroll.edit');
	Route::match(['put', 'patch'], 'payroll/{payroll}', [PayrollController::class, 'update'])->middleware('menu_access:payroll')->name('payroll.update');
	Route::delete('payroll/{payroll}', [PayrollController::class, 'destroy'])->middleware('menu_access:payroll')->name('payroll.destroy');

	Route::get('profile', [UserProfileController::class, 'index'])->name('profile');

	Route::get('rtl', function () {
		return view('rtl');
	})->name('rtl');

	Route::redirect('user-management', 'users')->name('user-management');
	Route::get('users-export', [UserController::class, 'export'])->middleware('menu_access:users')->name('users.export');
	Route::get('employees-export', [EmployeeController::class, 'export'])->middleware('menu_access:employees')->name('employees.export');
	Route::get('leaves-export', [LeaveController::class, 'export'])->middleware('menu_access:leaves')->name('leaves.export');
	Route::get('leaves/anomalies/export/pdf', [LeavesAnomalyExportController::class, 'pdf'])->middleware('menu_access:leaves')->name('leaves.anomalies.export.pdf');
	Route::get('leaves/anomalies/export/xlsx', [LeavesAnomalyExportController::class, 'xlsx'])->middleware('menu_access:leaves')->name('leaves.anomalies.export.xlsx');
	Route::get('leaves/anomalies/resolutions', [LeavesAnomalyResolutionController::class, 'index'])->middleware('menu_access:leaves')->name('leaves.anomalies.resolutions');
	Route::get('leaves/anomalies/resolutions/audit', [LeavesAnomalyResolutionController::class, 'audit'])->middleware('menu_access:leaves')->name('leaves.anomalies.resolutions.audit');
	Route::get('leaves/anomalies/resolutions/log', [LeavesAnomalyResolutionController::class, 'log'])->middleware('menu_access:leaves')->name('leaves.anomalies.resolutions.log');
	Route::get('leaves/anomalies/resolutions/trends', [LeavesAnomalyResolutionController::class, 'trends'])->middleware('menu_access:leaves')->name('leaves.anomalies.resolutions.trends');
	Route::get('leaves/anomalies/resolutions/audit/export/pdf', [LeavesAnomalyResolutionExportController::class, 'auditDashboardPdf'])->middleware('menu_access:leaves')->name('leaves.anomalies.resolutions.audit.export.pdf');
	Route::get('leaves/anomalies/resolutions/audit/export/xlsx', [LeavesAnomalyResolutionExportController::class, 'auditDashboardXlsx'])->middleware('menu_access:leaves')->name('leaves.anomalies.resolutions.audit.export.xlsx');
	Route::get('leaves/anomalies/resolutions/log/export/pdf', [LeavesAnomalyResolutionExportController::class, 'auditLogPdf'])->middleware('menu_access:leaves')->name('leaves.anomalies.resolutions.log.export.pdf');
	Route::get('leaves/anomalies/resolutions/log/export/xlsx', [LeavesAnomalyResolutionExportController::class, 'auditLogXlsx'])->middleware('menu_access:leaves')->name('leaves.anomalies.resolutions.log.export.xlsx');
	Route::get('leaves/anomalies/resolutions/export/pdf', [LeavesAnomalyResolutionExportController::class, 'pdf'])->middleware('menu_access:leaves')->name('leaves.anomalies.resolutions.export.pdf');
	Route::get('leaves/anomalies/resolutions/export/xlsx', [LeavesAnomalyResolutionExportController::class, 'xlsx'])->middleware('menu_access:leaves')->name('leaves.anomalies.resolutions.export.xlsx');
	Route::get('leaves/{employee}/export/csv', [LeavesExportController::class, 'employeeCsv'])->middleware('menu_access:leaves')->name('leaves.employee.export.csv');
	Route::get('leaves/{employee}/export/xlsx', [LeavesExportController::class, 'employeeXlsx'])->middleware('menu_access:leaves')->name('leaves.employee.export.xlsx');
	Route::get('leaves/{employee}/export/aggregation/xlsx', [LeavesAggregationExportController::class, 'employeeXlsx'])->middleware('menu_access:leaves')->name('leaves.employee.export.aggregation.xlsx');
	Route::get('leaves/analytics', [LeavesAnalyticsController::class, 'index'])->middleware('menu_access:leaves')->name('leaves.analytics');
	Route::get('leaves/reports', [LeavesAnalyticsController::class, 'index'])->middleware('menu_access:leaves.reports')->name('leaves.reports');
	Route::get('leaves/anomalies/trends', [LeavesAnomalyController::class, 'trends'])->middleware('menu_access:leaves')->name('leaves.anomalies.trends');
	Route::get('leaves/anomalies', [LeavesAnomalyController::class, 'index'])->middleware('menu_access:leaves')->name('leaves.anomalies');
	Route::post('leaves/anomalies/{id}/resolve', [LeavesAnomalyResolutionController::class, 'store'])->middleware('menu_access:leaves')->name('leaves.anomalies.resolve');
	Route::patch('leaves/anomalies/notifications/{notification}/read', [LeavesAnomalyController::class, 'markRead'])->middleware('menu_access:leaves')->name('leaves.anomalies.notifications.read');
	Route::patch('leaves/anomalies/notifications/{notification}/unread', [LeavesAnomalyController::class, 'markUnread'])->middleware('menu_access:leaves')->name('leaves.anomalies.notifications.unread');
	Route::get('lembur', [LemburController::class, 'index'])->middleware('menu_access:lembur.submit')->name('lembur.index');
	Route::get('lembur/approval', [LemburController::class, 'approval'])->middleware('menu_access:lembur.approval')->name('lembur.approval');
	Route::get('lembur/create', [LemburController::class, 'create'])->middleware('menu_access:lembur.submit')->name('lembur.create');
	Route::post('lembur', [LemburController::class, 'store'])->middleware('menu_access:lembur.submit')->name('lembur.store');
	Route::post('lembur/{lembur}/approve', [LemburController::class, 'approve'])->middleware('menu_access:lembur.approval')->name('lembur.approve');
	Route::post('lembur/{lembur}/reject', [LemburController::class, 'reject'])->middleware('menu_access:lembur.approval')->name('lembur.reject');
	Route::get('lembur/reports', [LemburController::class, 'reports'])->middleware('menu_access:lembur.reports')->name('lembur.reports');
	Route::get('lembur/export/xlsx', [LemburController::class, 'export'])->middleware('menu_access:lembur.reports')->name('lembur.export');
	Route::get('lembur/export/pdf', [LemburController::class, 'exportPdf'])->middleware('menu_access:lembur.reports')->name('lembur.export.pdf');
	Route::get('employees/{employee}/leaves', [LeaveController::class, 'show'])->middleware('menu_access:leaves')->name('employees.leaves.show');
	Route::get('employees/{employee}/leaves-export', [LeaveController::class, 'exportEmployee'])->middleware('menu_access:leaves')->name('employees.leaves.export');
	Route::resource('employees', EmployeeController::class)->middleware('menu_access:employees')->except('show');
	Route::get('employees/{employee}', [EmployeeController::class, 'show'])->middleware('menu_access:employees')->name('employees.show');

	// Family Members (nested under employee)
	Route::post('family-members/{employee}', [FamilyMembersController::class, 'store'])->middleware('menu_access:employees')->name('family-members.store');
	Route::put('family-members/{employee}/{familyMember}', [FamilyMembersController::class, 'update'])->middleware('menu_access:employees')->name('family-members.update');
	Route::post('employees/{employee}/family-members', [FamilyMembersController::class, 'store'])->middleware('menu_access:employees')->name('employees.family-members.store');
	Route::put('employees/{employee}/family-members/{familyMember}', [FamilyMembersController::class, 'update'])->middleware('menu_access:employees')->name('employees.family-members.update');
	Route::delete('employees/{employee}/family-members/{familyMember}', [FamilyMembersController::class, 'destroy'])->middleware('menu_access:employees')->name('employees.family-members.destroy');

	// Bank Accounts (nested under employee)
	Route::post('employees/{employee}/bank-accounts', [BankAccountsController::class, 'store'])->middleware('menu_access:employees')->name('employees.bank-accounts.store');
	Route::put('employees/{employee}/bank-accounts/{bankAccount}', [BankAccountsController::class, 'update'])->middleware('menu_access:employees')->name('employees.bank-accounts.update');
	Route::delete('employees/{employee}/bank-accounts/{bankAccount}', [BankAccountsController::class, 'destroy'])->middleware('menu_access:employees')->name('employees.bank-accounts.destroy');

	Route::post('departments/{department}/positions', [PositionController::class, 'store'])->middleware('menu_access:departments')->name('departments.positions.store');
	Route::put('departments/{department}/positions/{position}', [PositionController::class, 'update'])->middleware('menu_access:departments')->name('departments.positions.update');
	Route::delete('departments/{department}/positions/{position}', [PositionController::class, 'destroy'])->middleware('menu_access:departments')->name('departments.positions.destroy');
	Route::get('departments/{department}/positions/export/csv', [PositionsExportController::class, 'csv'])->middleware('menu_access:departments')->name('departments.positions.export.csv');
	Route::get('departments/{department}/positions/export/xlsx', [PositionsExportController::class, 'xlsx'])->middleware('menu_access:departments')->name('departments.positions.export.xlsx');
	Route::get('positions/import/template', [PositionController::class, 'downloadImportTemplate'])->middleware('menu_access:positions')->name('positions.import.template');
	Route::post('positions/import', [PositionController::class, 'import'])->middleware('menu_access:positions')->name('positions.import');
	Route::get('positions/export/xlsx', [PositionsExportController::class, 'indexXlsx'])->middleware('menu_access:positions')->name('positions.export.xlsx');
	Route::get('departments/export', [DepartmentController::class, 'export'])->middleware('menu_access:departments')->name('departments.export');
	Route::get('departments/import/template', [DepartmentController::class, 'downloadImportTemplate'])->middleware('menu_access:departments')->name('departments.import.template');
	Route::post('departments/import', [DepartmentController::class, 'import'])->middleware('menu_access:departments')->name('departments.import');

	Route::resource('leaves', LeaveController::class)->middleware('menu_access:leaves')->except('show');
	Route::resource('jenis-cuti', JenisCutiController::class)->middleware('menu_access:leaves')->except(['show', 'destroy']);
	Route::get('attendances/analytics', [AttendancesAnalyticsController::class, 'index'])->middleware('menu_access:attendances')->name('attendances.analytics');
	Route::get('attendances/analytics/export/pdf', [AttendancesAnalyticsExportController::class, 'pdf'])->middleware('menu_access:attendances')->name('attendances.analytics.export.pdf');
	Route::get('attendances/analytics/export/xlsx', [AttendancesAnalyticsExportController::class, 'xlsx'])->middleware('menu_access:attendances')->name('attendances.analytics.export.xlsx');
	Route::get('attendances/dashboard', [AttendancesDashboardController::class, 'index'])->middleware('menu_access:attendances')->name('attendances.dashboard');
	Route::get('attendances/{employee}/export/csv', [AttendancesExportController::class, 'employeeCsv'])->middleware('menu_access:attendances')->name('attendances.employee.export.csv');
	Route::get('attendances/{employee}/export/xlsx', [AttendancesExportController::class, 'employeeXlsx'])->middleware('menu_access:attendances')->name('attendances.employee.export.xlsx');
	Route::get('attendances/export/csv', [AttendancesExportController::class, 'csv'])->middleware('menu_access:attendances')->name('attendances.export.csv');
	Route::get('attendances/export/xlsx', [AttendancesExportController::class, 'xlsx'])->middleware('menu_access:attendances')->name('attendances.export.xlsx');
	Route::post('attendances/self-service', [AttendanceController::class, 'selfService'])->middleware('menu_access:attendances')->name('attendances.self-service');
	Route::resource('attendances', AttendanceController::class)->middleware('menu_access:attendances')->except('show');
	Route::resource('work_locations', WorkLocationController::class)->middleware('menu_access:work_locations')->except('show');
	Route::resource('positions', PositionController::class)->middleware('menu_access:positions');
	Route::resource('departments', DepartmentController::class)->middleware('menu_access:departments');
	Route::redirect('tenant-management', 'tenants')->name('tenant-management');
	Route::delete('tenants/{tenant}/branding', [TenantController::class, 'destroyBranding'])->middleware('menu_access:tenants')->name('tenants.branding.destroy');
	Route::resource('tenants', TenantController::class)->middleware('menu_access:tenants')->scoped(['tenant' => 'slug']);
	Route::resource('roles', RolesController::class)->except('show');

	Route::get('tables', function () {
		return view('tables');
	})->name('tables');

    Route::get('virtual-reality', function () {
		return view('virtual-reality');
	})->name('virtual-reality');

    Route::get('static-sign-in', function () {
		return view('static-sign-in');
	})->name('sign-in');

    Route::get('static-sign-up', function () {
		return view('static-sign-up');
	})->name('sign-up');

    Route::get('/logout', [SessionsController::class, 'destroy']);
	Route::get('/user-profile/edit', [UserProfileController::class, 'edit'])->name('user-profile.edit');
	Route::get('/user-profile', [UserProfileController::class, 'index'])->name('user-profile.index');
	Route::match(['put', 'patch', 'post'], '/user-profile', [UserProfileController::class, 'update'])->name('user-profile.update');
	Route::get('/user-profile/{user}', [UserProfileController::class, 'show'])->middleware('menu_access:users')->name('users.show-profile');
	Route::delete('/user-profile/{user}', [UserProfileController::class, 'destroy'])->middleware('menu_access:users')->name('users.show-profile.destroy');
	Route::get('/user-profile/{user}/edit', [UserController::class, 'edit'])->middleware('menu_access:users')->name('users.profile-edit');
	Route::get('/users/{user}/link-employee', [UserController::class, 'linkEmployee'])->middleware('menu_access:users')->name('users.link-employee');
	Route::resource('users', UserController::class)->middleware('menu_access:users')->except('show');

	// Absence rules
	Route::get('absence-rules', [AbsenceRuleController::class, 'index'])->name('absence_rules.index')->middleware('menu_access:attendances');
	Route::get('absence-rules/create', [AbsenceRuleController::class, 'create'])->name('absence_rules.create')->middleware('menu_access:attendances');
	Route::post('absence-rules', [AbsenceRuleController::class, 'store'])->name('absence_rules.store')->middleware('menu_access:attendances');

	// Deduction rules (tenant-scoped master potongan)
	Route::get('deduction-rules', [\App\Http\Controllers\DeductionRuleController::class, 'index'])->name('deduction_rules.index')->middleware('menu_access:payroll');
	Route::get('deduction-rules/create', [\App\Http\Controllers\DeductionRuleController::class, 'create'])->name('deduction_rules.create')->middleware('menu_access:payroll');
	Route::post('deduction-rules', [\App\Http\Controllers\DeductionRuleController::class, 'store'])->name('deduction_rules.store')->middleware('menu_access:payroll');
	Route::get('deduction-rules/{rule}/edit', [\App\Http\Controllers\DeductionRuleController::class, 'edit'])->name('deduction_rules.edit')->middleware('menu_access:payroll');
	Route::match(['put','patch'], 'deduction-rules/{rule}', [\App\Http\Controllers\DeductionRuleController::class, 'update'])->name('deduction_rules.update')->middleware('menu_access:payroll');
	Route::delete('deduction-rules/{rule}', [\App\Http\Controllers\DeductionRuleController::class, 'destroy'])->name('deduction_rules.destroy')->middleware('menu_access:payroll');
});



Route::group(['middleware' => 'guest'], function () {
    Route::get('/register', [RegisterController::class, 'create']);
    Route::post('/register', [RegisterController::class, 'store']);
	Route::get('/login', [SessionsController::class, 'create'])->name('login')->middleware('guest');
	Route::post('/session', [SessionsController::class, 'store'])->name('session.store')->middleware('guest');
	Route::get('/session', fn () => redirect()->route('login'))->middleware('guest');
	Route::get('/login/forgot-password', [ResetController::class, 'create']);
	Route::post('/forgot-password', [ResetController::class, 'sendEmail']);
	Route::get('/reset-password/{token}', [ResetController::class, 'resetPass'])->name('password.reset');
	Route::post('/reset-password', [ChangePasswordController::class, 'changePassword'])->name('password.update');

});
