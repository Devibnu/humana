<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_route_is_accessible_by_admin_and_exposes_summary_and_chart_data(): void
    {
        $tenant = Tenant::create([
            'name' => 'Dashboard Tenant',
            'slug' => 'dashboard-tenant',
            'domain' => 'dashboard-tenant.test',
            'status' => 'active',
        ]);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dashboard Admin',
            'email' => 'dashboard-admin@example.test',
            'password' => 'password',
            'role' => 'admin_hr',
            'status' => 'active',
        ]);

        $positionA = Position::create([
            'tenant_id' => $tenant->id,
            'name' => 'HR Lead',
            'status' => 'active',
        ]);

        $positionB = Position::create([
            'tenant_id' => $tenant->id,
            'name' => 'Recruiter',
            'status' => 'active',
        ]);

        $departmentA = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'People Operations',
            'status' => 'active',
        ]);

        $departmentB = Department::create([
            'tenant_id' => $tenant->id,
            'name' => 'Talent Acquisition',
            'status' => 'active',
        ]);

        $workLocationA = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Head Office',
            'address' => 'Jakarta',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 150,
        ]);

        $workLocationB = WorkLocation::create([
            'tenant_id' => $tenant->id,
            'name' => 'Warehouse',
            'address' => 'Bekasi',
            'latitude' => -6.3,
            'longitude' => 107.0,
            'radius' => 200,
        ]);

        $employeeA = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'DB-EMP-001',
            'name' => 'Dashboard Employee A',
            'email' => 'dashboard-employee-a@example.test',
            'position_id' => $positionA->id,
            'department_id' => $departmentA->id,
            'work_location_id' => $workLocationA->id,
            'status' => 'active',
        ]);

        $employeeB = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'DB-EMP-002',
            'name' => 'Dashboard Employee B',
            'email' => 'dashboard-employee-b@example.test',
            'position_id' => $positionB->id,
            'department_id' => $departmentB->id,
            'work_location_id' => $workLocationB->id,
            'status' => 'active',
        ]);

        $employeeC = Employee::create([
            'tenant_id' => $tenant->id,
            'employee_code' => 'DB-EMP-003',
            'name' => 'Dashboard Employee C',
            'email' => 'dashboard-employee-c@example.test',
            'position_id' => $positionA->id,
            'department_id' => $departmentA->id,
            'status' => 'active',
        ]);

        $attendanceA = Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeA->id,
            'date' => now()->toDateString(),
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        $attendanceB = Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeB->id,
            'date' => now()->toDateString(),
            'check_in' => '08:15',
            'check_out' => '17:15',
            'status' => 'late',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeA->id,
            'date' => now()->subDay()->toDateString(),
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ]);

        Attendance::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeC->id,
            'date' => now()->toDateString(),
            'status' => 'absent',
        ]);

        AttendanceLog::create([
            'attendance_id' => $attendanceA->id,
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeA->id,
            'work_location_id' => $workLocationA->id,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance_meters' => 20,
        ]);

        AttendanceLog::create([
            'attendance_id' => $attendanceB->id,
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeB->id,
            'work_location_id' => $workLocationB->id,
            'latitude' => -6.3,
            'longitude' => 107.0,
            'distance_meters' => 15,
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeA->id,
            'leave_type' => 'annual',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'reason' => 'Pending leave',
            'status' => 'pending',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeA->id,
            'leave_type' => 'sick',
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'reason' => 'Approved leave',
            'status' => 'approved',
        ]);

        Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employeeB->id,
            'leave_type' => 'annual',
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'reason' => 'Rejected leave',
            'status' => 'rejected',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard.index');
        $response->assertSee('Ringkasan Dashboard HR');
        $response->assertSee('Total Karyawan');
        $response->assertSee('Absensi Hari Ini');
        $response->assertSee('Cuti Menunggu Persetujuan');
        $response->assertSee('Total Lokasi Kerja');
        $response->assertSee('Total Posisi');
        $response->assertSee('Total Departemen');

        $this->assertSame(3, $response->viewData('employeesTotal'));
        $this->assertSame(3, $response->viewData('attendancesTodayTotal'));
        $this->assertSame(1, $response->viewData('leavesPendingApprovalTotal'));
        $this->assertSame(2, $response->viewData('workLocationsTotal'));
        $this->assertSame(2, $response->viewData('positionsTotal'));
        $this->assertSame(2, $response->viewData('departmentsTotal'));

        $this->assertSame([
            'pending' => 1,
            'approved' => 1,
            'rejected' => 1,
        ], $response->viewData('leaveStatusSummary'));

        $this->assertSame([
            'present' => 1,
            'absent' => 1,
            'late' => 1,
        ], $response->viewData('attendanceStatusSummary'));

        $attendanceChart = $response->viewData('attendancePerWorkLocationChart');
        $leaveChart = $response->viewData('leaveStatusChart');

        $this->assertSame(['Head Office', 'Warehouse'], $attendanceChart['labels']);
        $this->assertSame([1, 1], $attendanceChart['counts']);
        $this->assertSame(['Menunggu', 'Disetujui', 'Ditolak'], $leaveChart['labels']);
        $this->assertSame([1, 1, 1], $leaveChart['counts']);
    }
}