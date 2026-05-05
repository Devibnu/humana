import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';
import 'dart:ui' as ui;

import 'package:camera/camera.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

const String apiBaseUrl = 'https://humana.ibnuapps.cloud/api/mobile';
const bool _showPayslipPreview = bool.fromEnvironment('SHOW_PAYSLIP_DEMO');
const String _previewScreen = String.fromEnvironment('PREVIEW_SCREEN');
const Color _primary = Color(0xFFcb0c9f);
const Color _ink = Color(0xFF27375F);
const Color _muted = Color(0xFF8392AB);
const Color _surface = Colors.white;
const Color _navy = Color(0xFF344767);

final Map<String, dynamic> _demoLeavePayload = {
  'employee': {
    'id': 12,
    'name': 'Raka Pratama',
    'employee_code': 'EMP-1002',
  },
  'summary': {
    'total': 3,
    'pending_requests': 1,
    'approved_requests': 2,
    'rejected_requests': 0,
    'approved_days': 4,
  },
  'leave_types': [
    {
      'id': 1,
      'name': 'Cuti Tahunan',
      'code': 'cuti-tahunan',
      'is_paid': true,
      'requires_attachment': false,
      'requires_approval': true,
      'approval_flow': 'single',
    },
    {
      'id': 2,
      'name': 'Cuti Sakit',
      'code': 'cuti-sakit',
      'is_paid': true,
      'requires_attachment': true,
      'requires_approval': true,
      'approval_flow': 'single',
    },
    {
      'id': 3,
      'name': 'Izin Pribadi',
      'code': 'izin-pribadi',
      'is_paid': false,
      'requires_attachment': false,
      'requires_approval': true,
      'approval_flow': 'single',
    },
  ],
  'data': [
    {
      'id': 31,
      'leave_type': {
        'id': 2,
        'name': 'Cuti Sakit',
        'code': 'cuti-sakit',
      },
      'start_date': '2026-05-03',
      'end_date': '2026-05-03',
      'duration_days': 1,
      'status': 'pending',
      'status_label': 'Pending',
      'reason': 'Demam tinggi dan istirahat dokter',
      'requires_attachment': true,
      'attachment_name': 'surat-dokter.pdf',
      'attachment_url': 'https://humana.ibnuapps.cloud/storage/leave-attachments/surat-dokter.pdf',
    },
    {
      'id': 30,
      'leave_type': {
        'id': 1,
        'name': 'Cuti Tahunan',
        'code': 'cuti-tahunan',
      },
      'start_date': '2026-04-21',
      'end_date': '2026-04-23',
      'duration_days': 3,
      'status': 'approved',
      'status_label': 'Approved',
      'reason': 'Libur keluarga',
      'requires_attachment': false,
      'attachment_name': null,
      'attachment_url': null,
    },
  ],
};

final Map<String, dynamic> _demoAttendanceStatus = {
  'employee': {
    'name': 'Raka Pratama',
    'employee_code': 'EMP-1002',
  },
  'work_location': {
    'name': 'Humana Office HQ',
    'radius': 150,
  },
  'work_schedule': {
    'name': 'Office',
    'jam_masuk': '08:30',
    'jam_pulang': '17:30',
  },
  'today_attendance': {
    'date': '2026-05-05',
    'check_in': '08:27',
    'check_out': '17:34',
    'distance_meters': 22,
    'late_minutes': 0,
    'early_leave_minutes': 0,
    'status': 'present',
  },
  'next_action': 'complete',
};

final List<Map<String, dynamic>> _demoAttendanceHistory = [
  {
    'date': '2026-05-05',
    'check_in': '08:27',
    'check_out': '17:34',
    'distance_meters': 22,
    'late_minutes': 0,
    'early_leave_minutes': 0,
    'status': 'present',
  },
  {
    'date': '2026-05-04',
    'check_in': '08:39',
    'check_out': '17:31',
    'distance_meters': 18,
    'late_minutes': 9,
    'early_leave_minutes': 0,
    'status': 'present',
  },
  {
    'date': '2026-05-03',
    'check_in': '08:31',
    'check_out': '17:10',
    'distance_meters': 25,
    'late_minutes': 1,
    'early_leave_minutes': 20,
    'status': 'present',
  },
];

final Map<String, dynamic> _demoOvertimePayload = {
  'employee': {
    'name': 'Raka Pratama',
    'employee_code': 'EMP-1002',
  },
  'settings': {
    'submission_role': 'karyawan',
  },
  'summary': {
    'total_hours': 9,
    'pending': 1,
    'approved': 2,
    'rejected': 0,
  },
  'data': [
    {
      'tanggal': '2026-05-02',
      'waktu_mulai': '18:00',
      'waktu_selesai': '21:00',
      'durasi_jam': 3,
      'status': 'pending',
      'status_label': 'Pending',
      'alasan': 'Closing payroll dan verifikasi final slip gaji.',
    },
    {
      'tanggal': '2026-04-28',
      'waktu_mulai': '18:30',
      'waktu_selesai': '22:30',
      'durasi_jam': 4,
      'status': 'approved',
      'status_label': 'Approved',
      'alasan': 'Support rekap absensi akhir bulan.',
    },
    {
      'tanggal': '2026-04-17',
      'waktu_mulai': '19:00',
      'waktu_selesai': '21:00',
      'durasi_jam': 2,
      'status': 'approved',
      'status_label': 'Approved',
      'alasan': 'Maintenance data attendance dan sinkron shift.',
    },
  ],
};

final List<Map<String, dynamic>> _demoPayslips = [
  {
    'period_start': '2026-04-01',
    'period_end': '2026-04-30',
    'base_salary': 6800000,
    'net_salary': 7525000,
    'allowances': {
      'transport': 350000,
      'meal': 450000,
      'health': 200000,
      'overtime': 875000,
      'total': 1875000,
    },
    'deductions': {
      'tax': 320000,
      'bpjs': 180000,
      'loan': 50000,
      'attendance': 600000,
      'total': 1150000,
    },
  },
  {
    'period_start': '2026-03-01',
    'period_end': '2026-03-31',
    'base_salary': 6800000,
    'net_salary': 7340000,
    'allowances': {
      'transport': 350000,
      'meal': 450000,
      'health': 200000,
      'overtime': 650000,
      'total': 1650000,
    },
    'deductions': {
      'tax': 320000,
      'bpjs': 180000,
      'loan': 50000,
      'attendance': 560000,
      'total': 1110000,
    },
  },
  {
    'period_start': '2026-02-01',
    'period_end': '2026-02-28',
    'base_salary': 6800000,
    'net_salary': 7180000,
    'allowances': {
      'transport': 350000,
      'meal': 420000,
      'health': 200000,
      'overtime': 520000,
      'total': 1490000,
    },
    'deductions': {
      'tax': 320000,
      'bpjs': 180000,
      'loan': 50000,
      'attendance': 740000,
      'total': 1290000,
    },
  },
];

final Map<String, dynamic> _demoPayslipSummary = {
  'total': _demoPayslips.length,
  'latest_net_salary': _demoPayslips.first['net_salary'],
  'average_net_salary': _demoPayslips
          .map((item) => _moneyNumber(item['net_salary']))
          .fold<double>(0, (sum, value) => sum + value) /
      _demoPayslips.length,
  'latest_total_allowance': (_demoPayslips.first['allowances']
      as Map<String, dynamic>)['total'],
};

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const HumanaEmployeeApp());
}

String _timeValue(dynamic value) {
  final text = value?.toString();
  if (text == null || text.isEmpty) {
    return '--:--';
  }

  return text.length >= 5 ? text.substring(0, 5) : text;
}

String _dateLabel(String? value) {
  if (value == null || value.isEmpty) {
    return '-';
  }

  final date = DateTime.tryParse(value);
  if (date == null) {
    return value;
  }

  const months = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'Mei',
    'Jun',
    'Jul',
    'Agu',
    'Sep',
    'Okt',
    'Nov',
    'Des',
  ];

  return '${date.day.toString().padLeft(2, '0')} ${months[date.month - 1]} ${date.year}';
}

String _todayLabel() {
  const days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
  final now = DateTime.now();

  return '${days[now.weekday - 1]}, ${_dateLabel(now.toIso8601String())}';
}

String _minutesLabel(int minutes) {
  if (minutes <= 0) {
    return 'Tepat waktu';
  }

  final hours = minutes ~/ 60;
  final rest = minutes % 60;
  if (hours > 0 && rest > 0) {
    return '${hours}j ${rest}m';
  }
  if (hours > 0) {
    return '${hours}j';
  }

  return '${minutes}m';
}

String _statusLabel(Map<String, dynamic> attendance) {
  final late = attendance['late_minutes'] as int? ?? 0;
  final early = attendance['early_leave_minutes'] as int? ?? 0;
  final status = attendance['status']?.toString();

  if (late > 0) {
    return 'Telat ${_minutesLabel(late)}';
  }
  if (early > 0) {
    return 'Pulang cepat ${_minutesLabel(early)}';
  }
  if (status == 'present') {
    return 'Hadir';
  }

  return status?.toUpperCase() ?? '-';
}

Color _statusColor(Map<String, dynamic> attendance) {
  final late = attendance['late_minutes'] as int? ?? 0;
  final early = attendance['early_leave_minutes'] as int? ?? 0;

  if (late > 0) {
    return const Color(0xFFE85D04);
  }
  if (early > 0) {
    return const Color(0xFFDD2D4A);
  }

  return const Color(0xFF2DCE89);
}

double _moneyNumber(dynamic value) {
  if (value is num) {
    return value.toDouble();
  }

  return double.tryParse(value?.toString() ?? '') ?? 0;
}

String _rupiah(dynamic value) {
  final amount = _moneyNumber(value).round().toString();
  final buffer = StringBuffer();

  for (var i = 0; i < amount.length; i++) {
    final position = amount.length - i;
    buffer.write(amount[i]);
    if (position > 1 && position % 3 == 1) {
      buffer.write('.');
    }
  }

  return 'Rp ${buffer.toString()}';
}

String _periodLabel(Map<String, dynamic> payslip) {
  final start = _dateLabel(payslip['period_start'] as String?);
  final end = _dateLabel(payslip['period_end'] as String?);

  if (start == '-' && end == '-') {
    return '-';
  }

  return '$start - $end';
}

void _noop() {}

class HumanaEmployeeApp extends StatelessWidget {
  const HumanaEmployeeApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Humana Employee',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFFcb0c9f),
          brightness: Brightness.light,
        ),
        scaffoldBackgroundColor: const Color(0xFFF5F7FB),
        appBarTheme: const AppBarTheme(
          backgroundColor: Colors.transparent,
          foregroundColor: Color(0xFF27375F),
          elevation: 0,
          centerTitle: false,
        ),
        useMaterial3: true,
      ),
      home: const AuthGate(),
    );
  }
}

class AuthGate extends StatefulWidget {
  const AuthGate({super.key});

  @override
  State<AuthGate> createState() => _AuthGateState();
}

class _AuthGateState extends State<AuthGate> {
  String? _token;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadToken();
  }

  Future<void> _loadToken() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _token = prefs.getString('auth_token');
      _loading = false;
    });
  }

  Future<void> _setToken(String? token) async {
    final prefs = await SharedPreferences.getInstance();
    if (token == null) {
      await prefs.remove('auth_token');
    } else {
      await prefs.setString('auth_token', token);
    }

    setState(() => _token = token);
  }

  @override
  Widget build(BuildContext context) {
    if (_showPayslipPreview) {
      return const EmployeeMobileShell(
        token: 'demo-preview-token',
        onLoggedOut: _noop,
        previewMode: true,
        initialIndex: 3,
      );
    }

    if (_previewScreen == 'leave') {
      return const EmployeeMobileShell(
        token: 'demo-preview-token',
        onLoggedOut: _noop,
        previewMode: true,
        initialIndex: 2,
      );
    }

    if (_previewScreen == 'attendance') {
      return const EmployeeMobileShell(
        token: 'demo-preview-token',
        onLoggedOut: _noop,
        previewMode: true,
        initialIndex: 0,
      );
    }

    if (_previewScreen == 'overtime') {
      return const EmployeeMobileShell(
        token: 'demo-preview-token',
        onLoggedOut: _noop,
        previewMode: true,
        initialIndex: 1,
      );
    }

    if (_previewScreen == 'employee-shell') {
      return const EmployeeMobileShell(
        token: 'demo-preview-token',
        onLoggedOut: _noop,
        previewMode: true,
      );
    }

    if (_loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    if (_token == null) {
      return LoginPage(onLoggedIn: _setToken);
    }

    return EmployeeMobileShell(
      token: _token!,
      onLoggedOut: () => _setToken(null),
    );
  }
}

class EmployeeMobileShell extends StatefulWidget {
  const EmployeeMobileShell({
    super.key,
    required this.token,
    required this.onLoggedOut,
    this.previewMode = false,
    this.initialIndex = 0,
  });

  final String token;
  final VoidCallback onLoggedOut;
  final bool previewMode;
  final int initialIndex;

  @override
  State<EmployeeMobileShell> createState() => _EmployeeMobileShellState();
}

class _EmployeeMobileShellState extends State<EmployeeMobileShell> {
  late int _selectedIndex;

  @override
  void initState() {
    super.initState();
    _selectedIndex = widget.initialIndex;
  }

  @override
  Widget build(BuildContext context) {
    final pages = [
      AttendanceHomePage(
        token: widget.token,
        onLoggedOut: widget.onLoggedOut,
        previewMode: widget.previewMode,
      ),
      OvertimePage(
        token: widget.token,
        onLoggedOut: widget.onLoggedOut,
        previewMode: widget.previewMode,
      ),
      LeaveRequestPage(
        token: widget.token,
        onLoggedOut: widget.onLoggedOut,
        previewMode: widget.previewMode,
      ),
      PayslipPage(
        token: widget.token,
        onLoggedOut: widget.onLoggedOut,
        previewMode: widget.previewMode,
      ),
      ProfilePage(onLoggedOut: widget.onLoggedOut),
    ];

    return Scaffold(
      body: IndexedStack(index: _selectedIndex, children: pages),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _selectedIndex,
        onDestinationSelected: (index) =>
            setState(() => _selectedIndex = index),
        height: 72,
        backgroundColor: _surface,
        indicatorColor: const Color(0xFFFFE7F8),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.event_available_outlined),
            selectedIcon: Icon(Icons.event_available_rounded),
            label: 'Absensi',
          ),
          NavigationDestination(
            icon: Icon(Icons.more_time_outlined),
            selectedIcon: Icon(Icons.more_time_rounded),
            label: 'Lembur',
          ),
          NavigationDestination(
            icon: Icon(Icons.beach_access_outlined),
            selectedIcon: Icon(Icons.beach_access_rounded),
            label: 'Cuti/Izin',
          ),
          NavigationDestination(
            icon: Icon(Icons.receipt_long_outlined),
            selectedIcon: Icon(Icons.receipt_long_rounded),
            label: 'Slip Gaji',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline_rounded),
            selectedIcon: Icon(Icons.person_rounded),
            label: 'Profil',
          ),
        ],
      ),
    );
  }
}

class LoginPage extends StatefulWidget {
  const LoginPage({super.key, required this.onLoggedIn});

  final Future<void> Function(String token) onLoggedIn;

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _loading = false;
  String? _error;

  Future<void> _login() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final response = await http.post(
        Uri.parse('$apiBaseUrl/login'),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'email': _emailController.text.trim(),
          'password': _passwordController.text,
          'device_name': Platform.operatingSystem,
        }),
      );

      final payload = jsonDecode(response.body) as Map<String, dynamic>;
      if (response.statusCode >= 400) {
        throw ApiException.fromPayload(payload);
      }

      await widget.onLoggedIn(payload['token'] as String);
    } catch (error) {
      setState(() => _error = error.toString());
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            const SizedBox(height: 48),
            const Icon(Icons.badge_rounded, size: 56, color: Color(0xFFcb0c9f)),
            const SizedBox(height: 20),
            Text(
              'Humana Employee',
              textAlign: TextAlign.center,
              style: Theme.of(
                context,
              ).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 8),
            Text(
              'Login karyawan untuk absensi selfie dan GPS.',
              textAlign: TextAlign.center,
              style: Theme.of(
                context,
              ).textTheme.bodyMedium?.copyWith(color: Colors.blueGrey),
            ),
            const SizedBox(height: 32),
            Card(
              elevation: 0,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(18),
              ),
              child: Padding(
                padding: const EdgeInsets.all(18),
                child: Column(
                  children: [
                    TextField(
                      controller: _emailController,
                      keyboardType: TextInputType.emailAddress,
                      decoration: const InputDecoration(
                        labelText: 'Email',
                        prefixIcon: Icon(Icons.email_outlined),
                      ),
                    ),
                    const SizedBox(height: 14),
                    TextField(
                      controller: _passwordController,
                      obscureText: true,
                      decoration: const InputDecoration(
                        labelText: 'Password',
                        prefixIcon: Icon(Icons.lock_outline),
                      ),
                    ),
                    if (_error != null) ...[
                      const SizedBox(height: 14),
                      Text(_error!, style: const TextStyle(color: Colors.red)),
                    ],
                    const SizedBox(height: 22),
                    SizedBox(
                      width: double.infinity,
                      height: 52,
                      child: FilledButton.icon(
                        onPressed: _loading ? null : _login,
                        icon: _loading
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                ),
                              )
                            : const Icon(Icons.login),
                        label: Text(_loading ? 'Masuk...' : 'Masuk'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class AttendanceHomePage extends StatefulWidget {
  const AttendanceHomePage({
    super.key,
    required this.token,
    required this.onLoggedOut,
    this.previewMode = false,
  });

  final String token;
  final VoidCallback onLoggedOut;
  final bool previewMode;

  @override
  State<AttendanceHomePage> createState() => _AttendanceHomePageState();
}

class _AttendanceHomePageState extends State<AttendanceHomePage> {
  Map<String, dynamic>? _status;
  List<dynamic> _history = [];
  bool _loading = true;
  bool _submitting = false;
  String? _message;

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ${widget.token}',
  };

  @override
  void initState() {
    super.initState();
    if (widget.previewMode) {
      _loadPreviewData();
      return;
    }
    _refresh();
  }

  void _loadPreviewData() {
    setState(() {
      _status = _demoAttendanceStatus;
      _history = _demoAttendanceHistory;
      _message = 'Mode preview HRIS aktif. Absensi, cuti/izin, dan slip gaji tampil dalam satu aplikasi mobile.';
      _loading = false;
    });
  }

  Future<void> _refresh() async {
    setState(() => _loading = true);
    try {
      final statusResponse = await http.get(
        Uri.parse('$apiBaseUrl/attendances/status'),
        headers: _headers,
      );
      final historyResponse = await http.get(
        Uri.parse('$apiBaseUrl/attendances/history'),
        headers: _headers,
      );

      if (statusResponse.statusCode == 401 ||
          historyResponse.statusCode == 401) {
        widget.onLoggedOut();
        return;
      }

      final statusPayload =
          jsonDecode(statusResponse.body) as Map<String, dynamic>;
      final historyPayload =
          jsonDecode(historyResponse.body) as Map<String, dynamic>;

      if (statusResponse.statusCode >= 400) {
        throw ApiException.fromPayload(statusPayload);
      }

      if (historyResponse.statusCode >= 400) {
        throw ApiException.fromPayload(historyPayload);
      }

      setState(() {
        _status = statusPayload;
        _history = historyPayload['data'] as List<dynamic>;
        _message = null;
      });
    } catch (error) {
      setState(() => _message = error.toString());
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _submitAttendance() async {
    setState(() {
      _submitting = true;
      _message = null;
    });

    try {
      final position = await _resolvePosition();
      if (!mounted) {
        return;
      }

      final photoData = await Navigator.of(context).push<String>(
        MaterialPageRoute(builder: (_) => const AttendanceCameraPage()),
      );

      if (photoData == null) {
        return;
      }

      final response = await http.post(
        Uri.parse('$apiBaseUrl/attendances/submit'),
        headers: _headers,
        body: jsonEncode({
          'latitude': position.latitude,
          'longitude': position.longitude,
          'photo': photoData,
        }),
      );

      final payload = jsonDecode(response.body) as Map<String, dynamic>;
      if (response.statusCode >= 400) {
        throw ApiException.fromPayload(payload);
      }

      setState(() => _message = payload['message'] as String?);
      await _refresh();
    } catch (error) {
      setState(() => _message = error.toString());
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  Future<Position> _resolvePosition() async {
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      throw Exception('Location Services belum aktif.');
    }

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }

    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      throw Exception('Izin lokasi ditolak.');
    }

    return Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(accuracy: LocationAccuracy.high),
    );
  }

  Future<void> _logout() async {
    await http.post(Uri.parse('$apiBaseUrl/logout'), headers: _headers);
    widget.onLoggedOut();
  }

  @override
  Widget build(BuildContext context) {
    final employee = _status?['employee'] as Map<String, dynamic>?;
    final location = _status?['work_location'] as Map<String, dynamic>?;
    final schedule = _status?['work_schedule'] as Map<String, dynamic>?;
    final attendance = _status?['today_attendance'] as Map<String, dynamic>?;
    final nextAction = _status?['next_action'] as String?;
    final isComplete = nextAction == 'complete';
    final presentCount = _history
        .where(
          (item) =>
              (item as Map<String, dynamic>)['status']?.toString() == 'present',
        )
        .length;
    final lateCount = _history
        .where(
          (item) =>
              ((item as Map<String, dynamic>)['late_minutes'] as int? ?? 0) > 0,
        )
        .length;
    final earlyCount = _history
        .where(
          (item) =>
              ((item as Map<String, dynamic>)['early_leave_minutes'] as int? ??
                  0) >
              0,
        )
        .length;

    return Scaffold(
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _refresh,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 28),
                children: [
                  _HomeHeader(
                    employeeName: employee?['name'] as String? ?? '-',
                    employeeCode:
                        employee?['employee_code'] as String? ?? 'Employee',
                    onRefresh: _refresh,
                    onLogout: _logout,
                  ),
                  const SizedBox(height: 18),
                  _TodayAttendanceCard(
                    attendance: attendance,
                    location: location,
                    schedule: schedule,
                    nextAction: nextAction ?? 'check_in',
                    submitting: _submitting,
                    onSubmit: isComplete || _submitting
                        ? null
                        : _submitAttendance,
                  ),
                  if (_message != null) ...[
                    const SizedBox(height: 12),
                    _MessageBox(message: _message!),
                  ],
                  const SizedBox(height: 14),
                  _WorkInfoGrid(location: location, schedule: schedule),
                  const SizedBox(height: 14),
                  _SummaryStrip(
                    presentCount: presentCount,
                    lateCount: lateCount,
                    earlyCount: earlyCount,
                  ),
                  const SizedBox(height: 22),
                  _HistorySection(
                    history: _history
                        .map((item) => item as Map<String, dynamic>)
                        .toList(),
                  ),
                ],
              ),
            ),
    );
  }
}

class OvertimePage extends StatefulWidget {
  const OvertimePage({
    super.key,
    required this.token,
    required this.onLoggedOut,
    this.previewMode = false,
  });

  final String token;
  final VoidCallback onLoggedOut;
  final bool previewMode;

  @override
  State<OvertimePage> createState() => _OvertimePageState();
}

class _OvertimePageState extends State<OvertimePage> {
  final _reasonController = TextEditingController();
  DateTime _selectedDate = DateTime.now();
  TimeOfDay _startTime = const TimeOfDay(hour: 18, minute: 0);
  TimeOfDay _endTime = const TimeOfDay(hour: 19, minute: 0);
  bool _loading = true;
  bool _submitting = false;
  String? _message;
  Map<String, dynamic>? _payload;
  List<Map<String, dynamic>> _history = [];

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ${widget.token}',
  };

  @override
  void initState() {
    super.initState();
    if (widget.previewMode) {
      _loadPreviewData();
      return;
    }
    _refresh();
  }

  @override
  void dispose() {
    _reasonController.dispose();
    super.dispose();
  }

  void _loadPreviewData() {
    final history = (_demoOvertimePayload['data'] as List<dynamic>)
        .map((item) => item as Map<String, dynamic>)
        .toList();

    _reasonController.text = 'Closing payroll dan support final checking HRIS.';

    setState(() {
      _payload = _demoOvertimePayload;
      _history = history;
      _selectedDate = DateTime(2026, 5, 2);
      _startTime = const TimeOfDay(hour: 18, minute: 0);
      _endTime = const TimeOfDay(hour: 21, minute: 0);
      _message = 'Mode preview HRIS aktif. Pengajuan lembur tidak dikirim ke server.';
      _loading = false;
    });
  }

  Future<void> _refresh() async {
    setState(() => _loading = true);
    try {
      final response = await http.get(
        Uri.parse('$apiBaseUrl/overtimes'),
        headers: _headers,
      );

      if (response.statusCode == 401) {
        widget.onLoggedOut();
        return;
      }

      final payload = jsonDecode(response.body) as Map<String, dynamic>;
      if (response.statusCode >= 400) {
        throw ApiException.fromPayload(payload);
      }

      setState(() {
        _payload = payload;
        _history = (payload['data'] as List<dynamic>? ?? [])
            .map((item) => item as Map<String, dynamic>)
            .toList();
        _message = null;
      });
    } catch (error) {
      setState(() => _message = error.toString());
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _submit() async {
    final start = DateTime(
      _selectedDate.year,
      _selectedDate.month,
      _selectedDate.day,
      _startTime.hour,
      _startTime.minute,
    );
    final end = DateTime(
      _selectedDate.year,
      _selectedDate.month,
      _selectedDate.day,
      _endTime.hour,
      _endTime.minute,
    );

    if (!end.isAfter(start)) {
      setState(() => _message = 'Jam selesai harus lebih besar dari jam mulai.');
      return;
    }

    setState(() {
      _submitting = true;
      _message = null;
    });

    try {
      final response = await http.post(
        Uri.parse('$apiBaseUrl/overtimes'),
        headers: _headers,
        body: jsonEncode({
          'waktu_mulai': start.toIso8601String(),
          'waktu_selesai': end.toIso8601String(),
          'alasan': _reasonController.text.trim(),
        }),
      );
      final payload = jsonDecode(response.body) as Map<String, dynamic>;

      if (response.statusCode == 401) {
        widget.onLoggedOut();
        return;
      }

      if (response.statusCode >= 400) {
        throw ApiException.fromPayload(payload);
      }

      _reasonController.clear();
      setState(() => _message = payload['message'] as String?);
      await _refresh();
    } catch (error) {
      setState(() => _message = error.toString());
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime.now().subtract(const Duration(days: 30)),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );

    if (picked != null) {
      setState(() => _selectedDate = picked);
    }
  }

  Future<void> _pickStartTime() async {
    final picked = await showTimePicker(context: context, initialTime: _startTime);
    if (picked != null) {
      setState(() => _startTime = picked);
    }
  }

  Future<void> _pickEndTime() async {
    final picked = await showTimePicker(context: context, initialTime: _endTime);
    if (picked != null) {
      setState(() => _endTime = picked);
    }
  }

  String _formatHours(dynamic value) {
    final hours = (value as num?)?.toDouble() ?? 0;
    if (hours == hours.roundToDouble()) {
      return hours.toStringAsFixed(0);
    }

    return hours.toStringAsFixed(1);
  }

  @override
  Widget build(BuildContext context) {
    final employee = _payload?['employee'] as Map<String, dynamic>?;
    final settings = _payload?['settings'] as Map<String, dynamic>?;
    final summary = _payload?['summary'] as Map<String, dynamic>?;
    final latest = _history.isEmpty ? null : _history.first;
    final submissionRole = settings?['submission_role']?.toString() ?? 'karyawan';
    final blockedByPolicy = submissionRole == 'atasan';

    return _MobilePageScaffold(
      title: 'Pengajuan Lembur',
      subtitle: 'Ajukan lembur langsung dari mobile.',
      icon: Icons.more_time_rounded,
      child: _loading
          ? const Center(child: Padding(
              padding: EdgeInsets.symmetric(vertical: 48),
              child: CircularProgressIndicator(),
            ))
          : Column(
              children: [
                _FeatureHeroCard(
                  title: 'Akumulasi Lembur',
                  value: '${_formatHours(summary?['total_hours'])} jam',
                  subtitle: latest == null
                      ? 'Belum ada riwayat lembur. Buat pengajuan baru dari form di bawah.'
                      : 'Status terakhir: ${latest['status_label'] ?? latest['status'] ?? '-'} pada ${_dateLabel(latest['tanggal']?.toString())}.',
                  icon: Icons.assignment_turned_in_rounded,
                  color: const Color(0xFF5E72E4),
                ),
                const SizedBox(height: 16),
                Wrap(
                  spacing: 12,
                  runSpacing: 12,
                  children: [
                    _MiniMetricCard(
                      label: 'Pending',
                      value: '${summary?['pending'] ?? 0}',
                    ),
                    _MiniMetricCard(
                      label: 'Disetujui',
                      value: '${summary?['approved'] ?? 0}',
                    ),
                    _MiniMetricCard(
                      label: 'Ditolak',
                      value: '${summary?['rejected'] ?? 0}',
                    ),
                  ],
                ),
                if (_message != null) ...[
                  const SizedBox(height: 12),
                  _MessageBox(message: _message!),
                ],
                const SizedBox(height: 16),
                _FormCard(
                  title: 'Ajukan Lembur',
                  children: [
                    _ReadonlyField(
                      label: 'Karyawan',
                      value: employee?['name']?.toString() ?? '-',
                    ),
                    const SizedBox(height: 12),
                    _ActionField(
                      label: 'Tanggal Lembur',
                      value: _dateLabel(_selectedDate.toIso8601String()),
                      icon: Icons.event_rounded,
                      onTap: _pickDate,
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: _ActionField(
                            label: 'Jam Mulai',
                            value: _startTime.format(context),
                            icon: Icons.schedule_rounded,
                            onTap: _pickStartTime,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _ActionField(
                            label: 'Jam Selesai',
                            value: _endTime.format(context),
                            icon: Icons.schedule_send_rounded,
                            onTap: _pickEndTime,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _reasonController,
                      minLines: 3,
                      maxLines: 4,
                      decoration: InputDecoration(
                        labelText: 'Alasan',
                        hintText: 'Contoh: closing operasional, maintenance, atau support event.',
                        filled: true,
                        fillColor: const Color(0xFFF8F9FA),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(18),
                          borderSide: BorderSide.none,
                        ),
                      ),
                    ),
                    if (blockedByPolicy) ...[
                      const SizedBox(height: 12),
                      const _MessageBox(
                        message: 'Tenant Anda mengharuskan pengajuan lembur dibuat oleh atasan, jadi form mobile tidak bisa mengirim pengajuan langsung.',
                      ),
                    ],
                    const SizedBox(height: 18),
                    SizedBox(
                      width: double.infinity,
                      height: 52,
                      child: FilledButton.icon(
                        onPressed: blockedByPolicy || _submitting ? null : _submit,
                        icon: _submitting
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Icon(Icons.send_rounded),
                        label: Text(_submitting ? 'Mengirim...' : 'Ajukan Lembur'),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                _ActivityListCard(
                  title: 'Riwayat Lembur',
                  emptyText: 'Belum ada riwayat lembur dari aplikasi mobile.',
                  children: _history.map((item) {
                    return _HistoryItem(
                      title: _dateLabel(item['tanggal']?.toString()),
                      subtitle:
                          '${_timeValue(item['waktu_mulai'])} - ${_timeValue(item['waktu_selesai'])} • ${_formatHours(item['durasi_jam'])} jam',
                      status: item['status_label']?.toString() ?? '-',
                      description: item['alasan']?.toString() ?? 'Tanpa keterangan',
                    );
                  }).toList(),
                ),
              ],
            ),
    );
  }
}

class LeaveRequestPage extends StatefulWidget {
  const LeaveRequestPage({
    super.key,
    required this.token,
    required this.onLoggedOut,
    this.previewMode = false,
  });

  final String token;
  final VoidCallback onLoggedOut;
  final bool previewMode;

  @override
  State<LeaveRequestPage> createState() => _LeaveRequestPageState();
}

class _LeaveRequestPageState extends State<LeaveRequestPage> {
  final _reasonController = TextEditingController();
  bool _loading = true;
  bool _submitting = false;
  String? _message;
  DateTime _startDate = DateTime.now();
  DateTime _endDate = DateTime.now();
  Map<String, dynamic>? _payload;
  List<Map<String, dynamic>> _history = [];
  List<Map<String, dynamic>> _leaveTypes = [];
  int? _selectedLeaveTypeId;
  PlatformFile? _selectedAttachment;

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': 'Bearer ${widget.token}',
  };

  @override
  void initState() {
    super.initState();
    if (widget.previewMode) {
      _loadPreviewData();
      return;
    }
    _refresh();
  }

  @override
  void dispose() {
    _reasonController.dispose();
    super.dispose();
  }

  Future<void> _refresh() async {
    setState(() => _loading = true);
    try {
      final response = await http.get(
        Uri.parse('$apiBaseUrl/leaves'),
        headers: _headers,
      );

      if (response.statusCode == 401) {
        widget.onLoggedOut();
        return;
      }

      final payload = jsonDecode(response.body) as Map<String, dynamic>;
      if (response.statusCode >= 400) {
        throw ApiException.fromPayload(payload);
      }

      final leaveTypes = (payload['leave_types'] as List<dynamic>? ?? [])
          .map((item) => item as Map<String, dynamic>)
          .toList();

      setState(() {
        _payload = payload;
        _leaveTypes = leaveTypes;
        _history = (payload['data'] as List<dynamic>? ?? [])
            .map((item) => item as Map<String, dynamic>)
            .toList();
        _selectedLeaveTypeId ??= leaveTypes.isEmpty
            ? null
            : leaveTypes.first['id'] as int?;
        _message = null;
      });
    } catch (error) {
      setState(() => _message = error.toString());
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  void _loadPreviewData() {
    final leaveTypes = (_demoLeavePayload['leave_types'] as List<dynamic>)
        .map((item) => item as Map<String, dynamic>)
        .toList();

    _reasonController.text = 'Istirahat sesuai anjuran dokter dan kontrol ulang.';

    setState(() {
      _payload = _demoLeavePayload;
      _leaveTypes = leaveTypes;
      _history = (_demoLeavePayload['data'] as List<dynamic>)
          .map((item) => item as Map<String, dynamic>)
          .toList();
      _selectedLeaveTypeId = 2;
      _selectedAttachment = PlatformFile(
        name: 'surat-dokter.pdf',
        size: 348000,
        bytes: Uint8List.fromList(List<int>.filled(16, 1)),
      );
      _startDate = DateTime(2026, 5, 6);
      _endDate = DateTime(2026, 5, 6);
      _message = 'Mode preview mobile aktif. Form ini tidak mengirim data ke server.';
      _loading = false;
    });
  }

  Future<void> _submit() async {
    if (widget.previewMode) {
      setState(() {
        _message = 'Preview mobile aktif. Upload dan submit live tetap menggunakan API produksi.';
      });
      return;
    }

    if (_selectedLeaveTypeId == null) {
      setState(() => _message = 'Pilih jenis cuti terlebih dahulu.');
      return;
    }

    final selectedLeaveType = _selectedLeaveType;
    final requiresAttachment =
        selectedLeaveType?['requires_attachment'] as bool? ?? false;

    if (_endDate.isBefore(_startDate)) {
      setState(() => _message = 'Tanggal selesai tidak boleh sebelum tanggal mulai.');
      return;
    }

    if (_reasonController.text.trim().isEmpty) {
      setState(() => _message = 'Keterangan pengajuan wajib diisi.');
      return;
    }

    if (requiresAttachment && _selectedAttachment == null) {
      setState(() => _message = 'Lampiran wajib dipilih untuk jenis cuti ini.');
      return;
    }

    setState(() {
      _submitting = true;
      _message = null;
    });

    try {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('$apiBaseUrl/leaves'),
      )
        ..headers.addAll({
          'Accept': 'application/json',
          'Authorization': 'Bearer ${widget.token}',
        })
        ..fields.addAll({
          'leave_type_id': '$_selectedLeaveTypeId',
          'start_date': _startDate.toIso8601String(),
          'end_date': _endDate.toIso8601String(),
          'reason': _reasonController.text.trim(),
        });

      if (_selectedAttachment != null) {
        final bytes = _selectedAttachment!.bytes;
        if (bytes == null) {
          throw Exception('Lampiran tidak dapat dibaca. Pilih ulang file.');
        }

        request.files.add(
          http.MultipartFile.fromBytes(
            'attachment',
            bytes,
            filename: _selectedAttachment!.name,
          ),
        );
      }

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);
      final payload = jsonDecode(response.body) as Map<String, dynamic>;

      if (response.statusCode == 401) {
        widget.onLoggedOut();
        return;
      }

      if (response.statusCode >= 400) {
        throw ApiException.fromPayload(payload);
      }

      _reasonController.clear();
      setState(() {
        _message = payload['message'] as String?;
        _endDate = _startDate;
        _selectedAttachment = null;
      });
      await _refresh();
    } catch (error) {
      setState(() => _message = error.toString());
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  Future<void> _pickStartDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _startDate,
      firstDate: DateTime.now().subtract(const Duration(days: 30)),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );

    if (picked != null) {
      setState(() {
        _startDate = picked;
        if (_endDate.isBefore(picked)) {
          _endDate = picked;
        }
      });
    }
  }

  Future<void> _pickEndDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _endDate,
      firstDate: _startDate,
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );

    if (picked != null) {
      setState(() => _endDate = picked);
    }
  }

  Future<void> _pickAttachment() async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: const ['pdf', 'jpg', 'jpeg', 'png'],
      withData: true,
    );

    if (result != null && result.files.isNotEmpty) {
      setState(() => _selectedAttachment = result.files.single);
    }
  }

  void _clearAttachment() {
    setState(() => _selectedAttachment = null);
  }

  String _fileSizeLabel(int bytes) {
    if (bytes >= 1024 * 1024) {
      return '${(bytes / (1024 * 1024)).toStringAsFixed(1)} MB';
    }
    if (bytes >= 1024) {
      return '${(bytes / 1024).toStringAsFixed(0)} KB';
    }

    return '$bytes B';
  }

  Map<String, dynamic>? get _selectedLeaveType {
    for (final type in _leaveTypes) {
      if (type['id'] == _selectedLeaveTypeId) {
        return type;
      }
    }

    return null;
  }

  @override
  Widget build(BuildContext context) {
    final employee = _payload?['employee'] as Map<String, dynamic>?;
    final summary = _payload?['summary'] as Map<String, dynamic>?;
    final selectedLeaveType = _selectedLeaveType;
    final requiresAttachment =
        selectedLeaveType?['requires_attachment'] as bool? ?? false;

    return _MobilePageScaffold(
      title: 'Cuti / Izin',
      subtitle: 'Pengajuan izin, sakit, dan cuti.',
      icon: Icons.beach_access_rounded,
      child: _loading
          ? const Center(child: Padding(
              padding: EdgeInsets.symmetric(vertical: 48),
              child: CircularProgressIndicator(),
            ))
          : Column(
              children: [
                _FeatureHeroCard(
                  title: 'Ringkasan Pengajuan',
                  value: '${summary?['approved_days'] ?? 0} hari disetujui',
                  subtitle:
                      '${summary?['pending_requests'] ?? 0} pengajuan masih menunggu proses approval.',
                  icon: Icons.calendar_month_rounded,
                  color: const Color(0xFF2DCE89),
                ),
                const SizedBox(height: 16),
                Wrap(
                  spacing: 12,
                  runSpacing: 12,
                  children: [
                    _MiniMetricCard(
                      label: 'Pending',
                      value: '${summary?['pending_requests'] ?? 0}',
                    ),
                    _MiniMetricCard(
                      label: 'Approved',
                      value: '${summary?['approved_requests'] ?? 0}',
                    ),
                    _MiniMetricCard(
                      label: 'Rejected',
                      value: '${summary?['rejected_requests'] ?? 0}',
                    ),
                  ],
                ),
                if (_message != null) ...[
                  const SizedBox(height: 12),
                  _MessageBox(message: _message!),
                ],
                const SizedBox(height: 16),
                _FormCard(
                  title: 'Ajukan Cuti / Izin',
                  children: [
                    _ReadonlyField(
                      label: 'Karyawan',
                      value: employee?['name']?.toString() ?? '-',
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int>(
                      initialValue: _selectedLeaveTypeId,
                      items: _leaveTypes
                          .map(
                            (type) => DropdownMenuItem<int>(
                              value: type['id'] as int?,
                              child: Text(type['name']?.toString() ?? '-'),
                            ),
                          )
                          .toList(),
                      decoration: InputDecoration(
                        labelText: 'Jenis Cuti',
                        filled: true,
                        fillColor: const Color(0xFFF8F9FA),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(18),
                          borderSide: BorderSide.none,
                        ),
                      ),
                      onChanged: (value) => setState(() => _selectedLeaveTypeId = value),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: _ActionField(
                            label: 'Mulai',
                            value: _dateLabel(_startDate.toIso8601String()),
                            icon: Icons.event_available_rounded,
                            onTap: _pickStartDate,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _ActionField(
                            label: 'Selesai',
                            value: _dateLabel(_endDate.toIso8601String()),
                            icon: Icons.event_busy_rounded,
                            onTap: _pickEndDate,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _reasonController,
                      minLines: 3,
                      maxLines: 4,
                      decoration: InputDecoration(
                        labelText: 'Keterangan',
                        hintText: 'Contoh: keperluan keluarga, istirahat, atau urusan pribadi.',
                        filled: true,
                        fillColor: const Color(0xFFF8F9FA),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(18),
                          borderSide: BorderSide.none,
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    _AttachmentPickerCard(
                      selectedFileName: _selectedAttachment?.name,
                      selectedFileSize: _selectedAttachment == null
                          ? null
                          : _fileSizeLabel(_selectedAttachment!.size),
                      requiredAttachment: requiresAttachment,
                      onPick: _pickAttachment,
                      onClear: _selectedAttachment == null ? null : _clearAttachment,
                    ),
                    const SizedBox(height: 18),
                    SizedBox(
                      width: double.infinity,
                      height: 52,
                      child: FilledButton.icon(
                        onPressed: _submitting ? null : _submit,
                        icon: _submitting
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Icon(Icons.add_rounded),
                        label: Text(_submitting ? 'Mengirim...' : 'Buat Pengajuan'),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                _ActivityListCard(
                  title: 'Riwayat Cuti / Izin',
                  emptyText: 'Belum ada riwayat cuti dari aplikasi mobile.',
                  children: _history.map((item) {
                    final leaveType = item['leave_type'] as Map<String, dynamic>?;
                    final attachmentName = item['attachment_name']?.toString();
                    final reason = item['reason']?.toString() ?? 'Tanpa keterangan';

                    return _HistoryItem(
                      title: leaveType?['name']?.toString() ?? 'Cuti / Izin',
                      subtitle:
                          '${_dateLabel(item['start_date']?.toString())} - ${_dateLabel(item['end_date']?.toString())} • ${item['duration_days'] ?? 0} hari',
                      status: item['status_label']?.toString() ?? '-',
                      description: attachmentName == null
                          ? reason
                          : '$reason • Lampiran tersedia',
                    );
                  }).toList(),
                ),
              ],
            ),
    );
  }
}

class PayslipPage extends StatefulWidget {
  const PayslipPage({
    super.key,
    required this.token,
    required this.onLoggedOut,
    this.previewMode = false,
  });

  final String token;
  final VoidCallback onLoggedOut;
  final bool previewMode;

  @override
  State<PayslipPage> createState() => _PayslipPageState();
}

class _PayslipPageState extends State<PayslipPage> {
  bool _loading = true;
  bool _showDemoData = false;
  String? _message;
  List<Map<String, dynamic>> _payslips = [];
  Map<String, dynamic>? _summary;

  Map<String, String> get _headers => {
    'Accept': 'application/json',
    'Authorization': 'Bearer ${widget.token}',
  };

  @override
  void initState() {
    super.initState();
    if (widget.previewMode) {
      _showDemoData = true;
      _loading = false;
      return;
    }
    _refresh();
  }

  Future<void> _refresh() async {
    try {
      final response = await http.get(
        Uri.parse('$apiBaseUrl/payslips'),
        headers: _headers,
      );

      if (response.statusCode == 401) {
        widget.onLoggedOut();
        return;
      }

      final payload = jsonDecode(response.body) as Map<String, dynamic>;
      if (response.statusCode >= 400) {
        throw ApiException.fromPayload(payload);
      }

      final data = (payload['data'] as List? ?? [])
          .map((item) => item as Map<String, dynamic>)
          .toList();

      if (!mounted) {
        return;
      }

      setState(() {
        _payslips = data;
        _summary = payload['summary'] as Map<String, dynamic>?;
        _message = null;
      });
    } catch (error) {
      if (mounted) {
        setState(() => _message = error.toString());
      }
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  void _showPayslipDetail(Map<String, dynamic> payslip) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _PayslipDetailSheet(payslip: payslip),
    );
  }

  List<Map<String, dynamic>> get _visiblePayslips =>
      _showDemoData ? _demoPayslips : _payslips;

  Map<String, dynamic>? get _visibleSummary =>
      _showDemoData ? _demoPayslipSummary : _summary;

  void _setDemoMode(bool enabled) {
    setState(() => _showDemoData = enabled);
  }

  @override
  Widget build(BuildContext context) {
    final payslips = _visiblePayslips;
    final summary = _visibleSummary;
    final latest = payslips.isNotEmpty ? payslips.first : null;
    final canPreview = _payslips.isEmpty || _message != null;

    return _MobilePageScaffold(
      title: 'Slip Gaji',
      subtitle: 'Ringkasan payroll pribadi.',
      icon: Icons.receipt_long_rounded,
      child: _loading
          ? const Padding(
              padding: EdgeInsets.only(top: 80),
              child: Center(child: CircularProgressIndicator()),
            )
          : Column(
              children: [
                _PayslipDataModeCard(
                  showDemoData: _showDemoData,
                  canPreview: canPreview,
                  onChanged: _setDemoMode,
                ),
                const SizedBox(height: 14),
                _FeatureHeroCard(
                  title: _showDemoData
                      ? 'Simulasi Take Home Pay'
                      : 'Take Home Pay Terakhir',
                  value: latest == null
                      ? 'Belum ada slip'
                      : _rupiah(latest['net_salary']),
                  subtitle: latest == null
                      ? 'Slip gaji akan tampil setelah payroll tersedia.'
                      : _showDemoData
                      ? 'Mode demo aktif untuk preview UI payroll mobile.'
                      : _periodLabel(latest),
                  icon: Icons.payments_rounded,
                  color: _navy,
                ),
                const SizedBox(height: 14),
                _PayslipInsightCard(
                  latestPayslip: latest,
                  isDemoMode: _showDemoData,
                ),
                if (_message != null) ...[
                  const SizedBox(height: 12),
                  _MessageBox(message: _message!),
                ],
                const SizedBox(height: 14),
                _PayslipSummaryCard(
                  total: (summary?['total'] as int?) ?? payslips.length,
                  latestNetSalary: summary?['latest_net_salary'],
                  averageNetSalary: summary?['average_net_salary'],
                  latestAllowance: summary?['latest_total_allowance'],
                ),
                const SizedBox(height: 18),
                _PayslipListSection(
                  payslips: payslips,
                  isDemoMode: _showDemoData,
                  onTap: _showPayslipDetail,
                ),
              ],
            ),
    );
  }
}

class _PayslipDataModeCard extends StatelessWidget {
  const _PayslipDataModeCard({
    required this.showDemoData,
    required this.canPreview,
    required this.onChanged,
  });

  final bool showDemoData;
  final bool canPreview;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: _surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFE9ECEF)),
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: const Color(0xFFFFE7F8),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(Icons.auto_awesome_rounded, color: _primary),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  showDemoData ? 'Mode simulasi aktif' : 'Data payroll live',
                  style: const TextStyle(
                    color: _ink,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  canPreview
                      ? 'Aktifkan simulasi untuk melihat preview slip gaji mobile.'
                      : 'Anda bisa pindah ke simulasi kapan saja untuk cek tampilan.' ,
                  style: const TextStyle(color: _muted, fontSize: 12),
                ),
              ],
            ),
          ),
          Switch.adaptive(value: showDemoData, onChanged: onChanged),
        ],
      ),
    );
  }
}

class _PayslipInsightCard extends StatelessWidget {
  const _PayslipInsightCard({
    required this.latestPayslip,
    required this.isDemoMode,
  });

  final Map<String, dynamic>? latestPayslip;
  final bool isDemoMode;

  @override
  Widget build(BuildContext context) {
    final allowances =
        latestPayslip?['allowances'] as Map<String, dynamic>? ?? {};
    final deductions =
        latestPayslip?['deductions'] as Map<String, dynamic>? ?? {};

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            const Color(0xFFF6F8FC),
            isDemoMode ? const Color(0xFFFFF1FB) : const Color(0xFFF1F7FF),
          ],
        ),
        borderRadius: BorderRadius.circular(26),
        border: Border.all(color: const Color(0xFFE4EAF3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Expanded(
                child: Text(
                  'Snapshot Payroll',
                  style: TextStyle(
                    color: _ink,
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: isDemoMode
                      ? const Color(0xFFFFE7F8)
                      : const Color(0xFFE8F7EF),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  isDemoMode ? 'Demo' : 'Live',
                  style: TextStyle(
                    color: isDemoMode ? _primary : const Color(0xFF1F8F5F),
                    fontWeight: FontWeight.w800,
                    fontSize: 12,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _PayslipHighlightPill(
                  label: 'Tunjangan',
                  value: _rupiah(allowances['total']),
                  color: const Color(0xFF11CDEF),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _PayslipHighlightPill(
                  label: 'Potongan',
                  value: _rupiah(deductions['total']),
                  color: const Color(0xFFFB6340),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _PayslipHighlightPill extends StatelessWidget {
  const _PayslipHighlightPill({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.75),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: _muted, fontSize: 12)),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(color: color, fontWeight: FontWeight.w900),
          ),
        ],
      ),
    );
  }
}

class _PayslipSummaryCard extends StatelessWidget {
  const _PayslipSummaryCard({
    required this.total,
    required this.latestNetSalary,
    required this.averageNetSalary,
    required this.latestAllowance,
  });

  final int total;
  final dynamic latestNetSalary;
  final dynamic averageNetSalary;
  final dynamic latestAllowance;

  @override
  Widget build(BuildContext context) {
    final metrics = [
      _PayslipMetric(
        label: 'Total Slip',
        value: '$total',
        icon: Icons.folder_copy_rounded,
        color: const Color(0xFF11CDEF),
      ),
      _PayslipMetric(
        label: 'Terakhir',
        value: _rupiah(latestNetSalary),
        icon: Icons.account_balance_wallet_rounded,
        color: const Color(0xFF2DCE89),
      ),
      _PayslipMetric(
        label: 'Rata-rata',
        value: _rupiah(averageNetSalary),
        icon: Icons.stacked_line_chart_rounded,
        color: const Color(0xFF5E72E4),
      ),
    ];

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: _surface,
        borderRadius: BorderRadius.circular(28),
        border: Border.all(color: const Color(0xFFE9ECEF)),
      ),
      child: LayoutBuilder(
        builder: (context, constraints) {
          final itemWidth = constraints.maxWidth >= 520
              ? (constraints.maxWidth - 24) / 3
              : (constraints.maxWidth - 12) / 2;

          return Wrap(
            spacing: 12,
            runSpacing: 12,
            children: metrics
                .map(
                  (metric) => SizedBox(width: itemWidth, child: metric),
                )
                .toList(),
          );
        },
      ),
    );
  }
}

class _PayslipMetric extends StatelessWidget {
  const _PayslipMetric({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
  });

  final String label;
  final String value;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.14),
            borderRadius: BorderRadius.circular(16),
          ),
          child: Icon(icon, color: color),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: const TextStyle(color: _muted, fontSize: 12)),
              const SizedBox(height: 2),
              FittedBox(
                fit: BoxFit.scaleDown,
                alignment: Alignment.centerLeft,
                child: Text(
                  value,
                  style: const TextStyle(
                    color: _ink,
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _PayslipListSection extends StatelessWidget {
  const _PayslipListSection({
    required this.payslips,
    required this.isDemoMode,
    required this.onTap,
  });

  final List<Map<String, dynamic>> payslips;
  final bool isDemoMode;
  final ValueChanged<Map<String, dynamic>> onTap;

  @override
  Widget build(BuildContext context) {
    if (payslips.isEmpty) {
      return const _StatusListCard(
        title: 'Riwayat Slip Gaji',
        emptyText: 'Belum ada slip gaji untuk akun ini.',
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Expanded(
              child: Text(
                'Riwayat Slip Gaji',
                style: TextStyle(
                  color: _ink,
                  fontSize: 24,
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
            Text(
              isDemoMode ? 'Preview ${payslips.length} slip' : '${payslips.length} data',
              style: const TextStyle(
                color: _muted,
                fontSize: 16,
                fontWeight: FontWeight.w800,
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        ...payslips.map(
          (payslip) => Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: _PayslipTile(payslip: payslip, onTap: () => onTap(payslip)),
          ),
        ),
      ],
    );
  }
}

class _PayslipTile extends StatelessWidget {
  const _PayslipTile({required this.payslip, required this.onTap});

  final Map<String, dynamic> payslip;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final allowances = payslip['allowances'] as Map<String, dynamic>? ?? {};
    final deductions = payslip['deductions'] as Map<String, dynamic>? ?? {};

    return Material(
      color: _surface,
      borderRadius: BorderRadius.circular(26),
      child: InkWell(
        borderRadius: BorderRadius.circular(26),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(26),
            border: Border.all(color: const Color(0xFFE9ECEF)),
          ),
          child: Row(
            children: [
              Container(
                width: 54,
                height: 54,
                decoration: BoxDecoration(
                  color: const Color(0xFFFFE7F8),
                  borderRadius: BorderRadius.circular(18),
                ),
                child: const Icon(Icons.receipt_rounded, color: _primary),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _periodLabel(payslip),
                      style: const TextStyle(
                        color: _ink,
                        fontSize: 16,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 5),
                    Text(
                      'Tunjangan ${_rupiah(allowances['total'])} • Potongan ${_rupiah(deductions['total'])}',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: _muted),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 10),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  const Text('Net', style: TextStyle(color: _muted)),
                  Text(
                    _rupiah(payslip['net_salary']),
                    style: const TextStyle(
                      color: _ink,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _PayslipDetailSheet extends StatelessWidget {
  const _PayslipDetailSheet({required this.payslip});

  final Map<String, dynamic> payslip;

  @override
  Widget build(BuildContext context) {
    final allowances = payslip['allowances'] as Map<String, dynamic>? ?? {};
    final deductions = payslip['deductions'] as Map<String, dynamic>? ?? {};

    return DraggableScrollableSheet(
      initialChildSize: 0.72,
      minChildSize: 0.45,
      maxChildSize: 0.92,
      builder: (context, controller) => Container(
        padding: const EdgeInsets.fromLTRB(20, 10, 20, 24),
        decoration: const BoxDecoration(
          color: _surface,
          borderRadius: BorderRadius.vertical(top: Radius.circular(30)),
        ),
        child: ListView(
          controller: controller,
          children: [
            Center(
              child: Container(
                width: 44,
                height: 5,
                decoration: BoxDecoration(
                  color: const Color(0xFFE9ECEF),
                  borderRadius: BorderRadius.circular(99),
                ),
              ),
            ),
            const SizedBox(height: 18),
            Text(
              _periodLabel(payslip),
              style: const TextStyle(
                color: _ink,
                fontSize: 24,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 6),
            const Text(
              'Detail komponen slip gaji',
              style: TextStyle(color: _muted),
            ),
            const SizedBox(height: 18),
            _PayslipAmountRow(
              label: 'Gaji Pokok',
              value: _rupiah(payslip['base_salary']),
              strong: true,
            ),
            const Divider(height: 26),
            const _PayslipGroupTitle('Tunjangan'),
            _PayslipAmountRow(
              label: 'Transport',
              value: _rupiah(allowances['transport']),
            ),
            _PayslipAmountRow(
              label: 'Makan',
              value: _rupiah(allowances['meal']),
            ),
            _PayslipAmountRow(
              label: 'Kesehatan',
              value: _rupiah(allowances['health']),
            ),
            _PayslipAmountRow(
              label: 'Lembur',
              value: _rupiah(allowances['overtime']),
            ),
            _PayslipAmountRow(
              label: 'Total Tunjangan',
              value: _rupiah(allowances['total']),
              strong: true,
            ),
            const Divider(height: 26),
            const _PayslipGroupTitle('Potongan'),
            _PayslipAmountRow(
              label: 'Pajak',
              value: _rupiah(deductions['tax']),
            ),
            _PayslipAmountRow(
              label: 'BPJS',
              value: _rupiah(deductions['bpjs']),
            ),
            _PayslipAmountRow(
              label: 'Pinjaman',
              value: _rupiah(deductions['loan']),
            ),
            _PayslipAmountRow(
              label: 'Absensi',
              value: _rupiah(deductions['attendance']),
            ),
            _PayslipAmountRow(
              label: 'Total Potongan',
              value: _rupiah(deductions['total']),
              strong: true,
            ),
            const Divider(height: 26),
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: const Color(0xFF2DCE89).withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(22),
              ),
              child: _PayslipAmountRow(
                label: 'Take Home Pay',
                value: _rupiah(payslip['net_salary']),
                strong: true,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PayslipGroupTitle extends StatelessWidget {
  const _PayslipGroupTitle(this.text);

  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(
        text,
        style: const TextStyle(
          color: _ink,
          fontSize: 16,
          fontWeight: FontWeight.w900,
        ),
      ),
    );
  }
}

class _PayslipAmountRow extends StatelessWidget {
  const _PayslipAmountRow({
    required this.label,
    required this.value,
    this.strong = false,
  });

  final String label;
  final String value;
  final bool strong;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Expanded(
            child: Text(
              label,
              style: TextStyle(
                color: strong ? _ink : _muted,
                fontWeight: strong ? FontWeight.w900 : FontWeight.w600,
              ),
            ),
          ),
          Text(
            value,
            style: TextStyle(
              color: _ink,
              fontWeight: strong ? FontWeight.w900 : FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class ProfilePage extends StatelessWidget {
  const ProfilePage({super.key, required this.onLoggedOut});

  final VoidCallback onLoggedOut;

  @override
  Widget build(BuildContext context) {
    return _MobilePageScaffold(
      title: 'Profil',
      subtitle: 'Akun dan preferensi aplikasi.',
      icon: Icons.person_rounded,
      child: Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(22),
            decoration: BoxDecoration(
              color: _surface,
              borderRadius: BorderRadius.circular(28),
              border: Border.all(color: const Color(0xFFE9ECEF)),
            ),
            child: const Column(
              children: [
                CircleAvatar(
                  radius: 36,
                  backgroundColor: Color(0xFFFFE7F8),
                  child: Icon(Icons.person_rounded, color: _primary, size: 42),
                ),
                SizedBox(height: 14),
                Text(
                  'Raka Pratama',
                  style: TextStyle(
                    color: _ink,
                    fontSize: 22,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                SizedBox(height: 3),
                Text(
                  'raka.pratama@humana.test',
                  style: TextStyle(color: _muted),
                ),
                SizedBox(height: 3),
                Text('EMP-1002 • Office', style: TextStyle(color: _muted)),
              ],
            ),
          ),
          const SizedBox(height: 16),
          _ProfileActionTile(
            icon: Icons.lock_outline_rounded,
            title: 'Keamanan Akun',
            subtitle: 'Password dan sesi perangkat',
            onTap: () {},
          ),
          const SizedBox(height: 10),
          _ProfileActionTile(
            icon: Icons.notifications_none_rounded,
            title: 'Notifikasi',
            subtitle: 'Pengingat absensi dan approval',
            onTap: () {},
          ),
          const SizedBox(height: 10),
          _ProfileActionTile(
            icon: Icons.logout_rounded,
            title: 'Keluar',
            subtitle: 'Logout dari aplikasi mobile',
            onTap: onLoggedOut,
          ),
        ],
      ),
    );
  }
}

class _MobilePageScaffold extends StatelessWidget {
  const _MobilePageScaffold({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.child,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 18, 20, 28),
          children: [
            Row(
              children: [
                Container(
                  width: 52,
                  height: 52,
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFE7F8),
                    borderRadius: BorderRadius.circular(18),
                  ),
                  child: Icon(icon, color: _primary, size: 28),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: Theme.of(context).textTheme.headlineSmall
                            ?.copyWith(color: _ink, fontWeight: FontWeight.w900),
                      ),
                      const SizedBox(height: 3),
                      Text(subtitle, style: const TextStyle(color: _muted)),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),
            child,
          ],
        ),
      ),
    );
  }
}

class _FeatureHeroCard extends StatelessWidget {
  const _FeatureHeroCard({
    required this.title,
    required this.value,
    required this.subtitle,
    required this.icon,
    required this.color,
  });

  final String title;
  final String value;
  final String subtitle;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(28),
        boxShadow: [
          BoxShadow(
            color: color.withValues(alpha: 0.22),
            blurRadius: 22,
            offset: const Offset(0, 14),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.18),
              borderRadius: BorderRadius.circular(18),
            ),
            child: Icon(icon, color: Colors.white, size: 28),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: const TextStyle(color: Colors.white70)),
                const SizedBox(height: 3),
                Text(
                  value,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 22,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 3),
                Text(subtitle, style: const TextStyle(color: Colors.white70)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _FormCard extends StatelessWidget {
  const _FormCard({required this.title, required this.children});

  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: _surface,
        borderRadius: BorderRadius.circular(28),
        border: Border.all(color: const Color(0xFFE9ECEF)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              color: _ink,
              fontSize: 18,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 16),
          ...children,
        ],
      ),
    );
  }
}

class _ReadonlyField extends StatelessWidget {
  const _ReadonlyField({
    required this.label,
    required this.value,
  });

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      initialValue: value,
      readOnly: true,
      maxLines: 1,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: const Color(0xFFF8F9FA),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: BorderSide.none,
        ),
      ),
    );
  }
}

class _ActionField extends StatelessWidget {
  const _ActionField({
    required this.label,
    required this.value,
    required this.icon,
    required this.onTap,
  });

  final String label;
  final String value;
  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          filled: true,
          fillColor: const Color(0xFFF8F9FA),
          prefixIcon: Icon(icon),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(18),
            borderSide: BorderSide.none,
          ),
        ),
        child: Text(value, style: const TextStyle(color: _ink)),
      ),
    );
  }
}

class _AttachmentPickerCard extends StatelessWidget {
  const _AttachmentPickerCard({
    required this.selectedFileName,
    required this.selectedFileSize,
    required this.requiredAttachment,
    required this.onPick,
    this.onClear,
  });

  final String? selectedFileName;
  final String? selectedFileSize;
  final bool requiredAttachment;
  final VoidCallback onPick;
  final VoidCallback? onClear;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF8F9FA),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Expanded(
                child: Text(
                  'Lampiran Bukti',
                  style: TextStyle(color: _ink, fontWeight: FontWeight.w800),
                ),
              ),
              if (requiredAttachment)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFF4E5),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: const Text(
                    'Wajib',
                    style: TextStyle(
                      color: Color(0xFF7A4B00),
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            selectedFileName == null
                ? 'Format PDF/JPG/PNG, maksimal 2 MB.'
                : '$selectedFileName${selectedFileSize == null ? '' : ' • $selectedFileSize'}',
            style: const TextStyle(color: _muted),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              OutlinedButton.icon(
                onPressed: onPick,
                icon: const Icon(Icons.attach_file_rounded),
                label: Text(selectedFileName == null ? 'Pilih File' : 'Ganti File'),
              ),
              if (onClear != null)
                TextButton.icon(
                  onPressed: onClear,
                  icon: const Icon(Icons.close_rounded),
                  label: const Text('Hapus'),
                ),
            ],
          ),
        ],
      ),
    );
  }
}

class _MiniMetricCard extends StatelessWidget {
  const _MiniMetricCard({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 104,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: _surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE9ECEF)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: _muted, fontSize: 12)),
          const SizedBox(height: 6),
          Text(
            value,
            style: const TextStyle(
              color: _ink,
              fontSize: 18,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

class _ActivityListCard extends StatelessWidget {
  const _ActivityListCard({
    required this.title,
    required this.emptyText,
    required this.children,
  });

  final String title;
  final String emptyText;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: _surface,
        borderRadius: BorderRadius.circular(28),
        border: Border.all(color: const Color(0xFFE9ECEF)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              color: _ink,
              fontSize: 18,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 16),
          if (children.isEmpty)
            Center(
              child: Column(
                children: [
                  Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8F9FA),
                      borderRadius: BorderRadius.circular(18),
                    ),
                    child: const Icon(Icons.inbox_rounded, color: _muted),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    emptyText,
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: _muted),
                  ),
                ],
              ),
            )
          else
            ...children,
        ],
      ),
    );
  }
}

class _HistoryItem extends StatelessWidget {
  const _HistoryItem({
    required this.title,
    required this.subtitle,
    required this.status,
    required this.description,
  });

  final String title;
  final String subtitle;
  final String status;
  final String description;

  Color get _badgeColor {
    final normalized = status.toLowerCase();
    if (normalized.contains('setujui') || normalized.contains('approve')) {
      return const Color(0xFFE8FFF3);
    }
    if (normalized.contains('tolak') || normalized.contains('reject')) {
      return const Color(0xFFFFECEC);
    }

    return const Color(0xFFFFF7E8);
  }

  Color get _badgeTextColor {
    final normalized = status.toLowerCase();
    if (normalized.contains('setujui') || normalized.contains('approve')) {
      return const Color(0xFF18794E);
    }
    if (normalized.contains('tolak') || normalized.contains('reject')) {
      return const Color(0xFFC0392B);
    }

    return const Color(0xFF9A6700);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF8F9FA),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(
                    color: _ink,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: _badgeColor,
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  status,
                  style: TextStyle(
                    color: _badgeTextColor,
                    fontSize: 12,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(subtitle, style: const TextStyle(color: _muted)),
          const SizedBox(height: 8),
          Text(description, style: const TextStyle(color: _ink, height: 1.4)),
        ],
      ),
    );
  }
}

class _StatusListCard extends StatelessWidget {
  const _StatusListCard({required this.title, required this.emptyText});

  final String title;
  final String emptyText;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: _surface,
        borderRadius: BorderRadius.circular(28),
        border: Border.all(color: const Color(0xFFE9ECEF)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              color: _ink,
              fontSize: 18,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 16),
          Center(
            child: Column(
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8F9FA),
                    borderRadius: BorderRadius.circular(18),
                  ),
                  child: const Icon(Icons.inbox_rounded, color: _muted),
                ),
                const SizedBox(height: 10),
                Text(
                  emptyText,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: _muted),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ProfileActionTile extends StatelessWidget {
  const _ProfileActionTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: _surface,
      borderRadius: BorderRadius.circular(22),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(22),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: const Color(0xFFFFE7F8),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Icon(icon, color: _primary, size: 22),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        color: _ink,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(subtitle, style: const TextStyle(color: _muted)),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right_rounded, color: _muted),
            ],
          ),
        ),
      ),
    );
  }
}

class AttendanceCameraPage extends StatefulWidget {
  const AttendanceCameraPage({super.key});

  @override
  State<AttendanceCameraPage> createState() => _AttendanceCameraPageState();
}

class _AttendanceCameraPageState extends State<AttendanceCameraPage> {
  CameraController? _controller;
  bool _initializing = true;
  bool _capturing = false;
  String? _message;

  @override
  void initState() {
    super.initState();
    _initCamera();
  }

  Future<void> _initCamera() async {
    try {
      final cameras = await availableCameras();
      final frontCamera = cameras.firstWhere(
        (camera) => camera.lensDirection == CameraLensDirection.front,
        orElse: () => cameras.first,
      );

      final controller = CameraController(
        frontCamera,
        ResolutionPreset.high,
        enableAudio: false,
      );
      await controller.initialize();

      if (!mounted) {
        await controller.dispose();
        return;
      }

      setState(() {
        _controller = controller;
        _initializing = false;
      });
    } catch (error) {
      setState(() {
        _message = 'Tidak dapat membuka kamera: $error';
        _initializing = false;
      });
    }
  }

  Future<void> _capture() async {
    final controller = _controller;
    if (controller == null || !controller.value.isInitialized || _capturing) {
      return;
    }

    setState(() {
      _capturing = true;
      _message = 'Memeriksa wajah...';
    });

    try {
      final file = await controller.takePicture();
      final validation = await _validateFace(file);

      if (validation != null) {
        setState(() {
          _message = validation;
          _capturing = false;
        });
        return;
      }

      final bytes = await File(file.path).readAsBytes();
      final dataUrl = 'data:image/jpeg;base64,${base64Encode(bytes)}';

      if (mounted) {
        Navigator.of(context).pop(dataUrl);
      }
    } catch (error) {
      setState(() {
        _message = 'Foto belum valid: $error';
        _capturing = false;
      });
    }
  }

  Future<String?> _validateFace(XFile file) async {
    final bytes = await File(file.path).readAsBytes();
    final image = await _decodeImage(bytes);
    final width = image.width.toDouble();
    final height = image.height.toDouble();

    if (width < 640 || height < 640) {
      return 'Foto terlalu kecil. Dekatkan wajah ke kamera.';
    }

    return null;
  }

  Future<ui.Image> _decodeImage(Uint8List bytes) {
    final completer = Completer<ui.Image>();
    ui.decodeImageFromList(bytes, completer.complete);
    return completer.future;
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;

    return Scaffold(
      appBar: AppBar(title: const Text('Foto Live Absensi')),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Column(
            children: [
              Expanded(
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(24),
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      if (_initializing)
                        const Center(child: CircularProgressIndicator())
                      else if (controller != null &&
                          controller.value.isInitialized)
                        CameraPreview(controller)
                      else
                        const ColoredBox(color: Colors.black),
                      IgnorePointer(
                        child: Center(
                          child: Container(
                            width: 240,
                            height: 320,
                            decoration: BoxDecoration(
                              border: Border.all(color: Colors.white, width: 4),
                              borderRadius: BorderRadius.circular(160),
                            ),
                          ),
                        ),
                      ),
                      const Positioned(
                        left: 18,
                        right: 18,
                        bottom: 18,
                        child: DecoratedBox(
                          decoration: BoxDecoration(
                            color: Colors.black54,
                            borderRadius: BorderRadius.all(Radius.circular(14)),
                          ),
                          child: Padding(
                            padding: EdgeInsets.all(12),
                            child: Text(
                              'Wajah harus penuh, tidak terpotong, dan berada di tengah.',
                              textAlign: TextAlign.center,
                              style: TextStyle(color: Colors.white),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              if (_message != null) ...[
                const SizedBox(height: 12),
                _MessageBox(message: _message!),
              ],
              const SizedBox(height: 14),
              SizedBox(
                width: double.infinity,
                height: 54,
                child: FilledButton.icon(
                  onPressed: _capturing || _initializing ? null : _capture,
                  icon: _capturing
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.camera_alt),
                  label: Text(
                    _capturing ? 'Memeriksa...' : 'Ambil Foto & Simpan',
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _HomeHeader extends StatelessWidget {
  const _HomeHeader({
    required this.employeeName,
    required this.employeeCode,
    required this.onRefresh,
    required this.onLogout,
  });

  final String employeeName;
  final String employeeCode;
  final VoidCallback onRefresh;
  final VoidCallback onLogout;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      bottom: false,
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: const Color(0xFFFFE7F8),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(Icons.badge_rounded, color: _primary),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Humana Employee',
                  style: Theme.of(context).textTheme.labelLarge?.copyWith(
                    color: _muted,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  employeeName,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    color: _ink,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                Text(
                  employeeCode,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(color: _muted, fontSize: 12),
                ),
              ],
            ),
          ),
          _RoundIconButton(icon: Icons.refresh_rounded, onPressed: onRefresh),
          const SizedBox(width: 8),
          _RoundIconButton(icon: Icons.logout_rounded, onPressed: onLogout),
        ],
      ),
    );
  }
}

class _RoundIconButton extends StatelessWidget {
  const _RoundIconButton({required this.icon, required this.onPressed});

  final IconData icon;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: _surface,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onPressed,
        borderRadius: BorderRadius.circular(16),
        child: SizedBox(
          width: 44,
          height: 44,
          child: Icon(icon, color: _ink, size: 22),
        ),
      ),
    );
  }
}

class _TodayAttendanceCard extends StatelessWidget {
  const _TodayAttendanceCard({
    required this.attendance,
    required this.location,
    required this.schedule,
    required this.nextAction,
    required this.submitting,
    required this.onSubmit,
  });

  final Map<String, dynamic>? attendance;
  final Map<String, dynamic>? location;
  final Map<String, dynamic>? schedule;
  final String nextAction;
  final bool submitting;
  final VoidCallback? onSubmit;

  @override
  Widget build(BuildContext context) {
    final isComplete = nextAction == 'complete';
    final isCheckOut = nextAction == 'check_out';
    final buttonLabel = isComplete
        ? 'Absensi Lengkap'
        : isCheckOut
        ? 'Absen Pulang'
        : 'Absen Masuk';
    final lateMinutes = attendance?['late_minutes'] as int? ?? 0;
    final earlyMinutes = attendance?['early_leave_minutes'] as int? ?? 0;
    final statusText = isComplete
        ? 'Hari ini sudah selesai'
        : isCheckOut
        ? 'Masuk tercatat, lanjut pulang'
        : 'Siap absen masuk';

    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(30),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF172B4D), Color(0xFF7B1FA2), Color(0xFFE10098)],
        ),
        boxShadow: [
          BoxShadow(
            color: _primary.withValues(alpha: 0.24),
            blurRadius: 24,
            offset: const Offset(0, 16),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  _todayLabel(),
                  style: const TextStyle(
                    color: Colors.white70,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 7,
                ),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.18),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  isComplete ? 'Lengkap' : 'Aktif',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w800,
                    fontSize: 12,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            statusText,
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w900,
              height: 1.08,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            '${location?['name'] ?? 'Lokasi belum diatur'} • ${schedule?['name'] ?? 'Jadwal belum diatur'}',
            style: const TextStyle(color: Colors.white70),
          ),
          const SizedBox(height: 22),
          Row(
            children: [
              Expanded(
                child: _TimeBox(
                  icon: Icons.login_rounded,
                  title: 'Masuk',
                  value: _timeValue(attendance?['check_in']),
                  note: lateMinutes > 0
                      ? 'Telat ${_minutesLabel(lateMinutes)}'
                      : 'Check-in',
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _TimeBox(
                  icon: Icons.logout_rounded,
                  title: 'Pulang',
                  value: _timeValue(attendance?['check_out']),
                  note: earlyMinutes > 0
                      ? 'Cepat ${_minutesLabel(earlyMinutes)}'
                      : 'Check-out',
                ),
              ),
            ],
          ),
          const SizedBox(height: 22),
          SizedBox(
            width: double.infinity,
            height: 56,
            child: FilledButton.icon(
              style: FilledButton.styleFrom(
                backgroundColor: Colors.white,
                foregroundColor: _ink,
                disabledBackgroundColor: Colors.white.withValues(alpha: 0.72),
                disabledForegroundColor: _ink.withValues(alpha: 0.6),
                textStyle: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w900,
                ),
              ),
              onPressed: onSubmit,
              icon: submitting
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : Icon(
                      isComplete ? Icons.verified_rounded : Icons.camera_alt,
                    ),
              label: Text(submitting ? 'Menyimpan...' : buttonLabel),
            ),
          ),
        ],
      ),
    );
  }
}

class _TimeBox extends StatelessWidget {
  const _TimeBox({
    required this.icon,
    required this.title,
    required this.value,
    required this.note,
  });

  final IconData icon;
  final String title;
  final String value;
  final String note;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white.withValues(alpha: 0.16)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: Colors.white, size: 20),
          const SizedBox(height: 10),
          Text(
            title,
            style: const TextStyle(color: Colors.white70, fontSize: 12),
          ),
          const SizedBox(height: 2),
          Text(
            value,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 22,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 3),
          Text(
            note,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(color: Colors.white70, fontSize: 11),
          ),
        ],
      ),
    );
  }
}

class _WorkInfoGrid extends StatelessWidget {
  const _WorkInfoGrid({required this.location, required this.schedule});

  final Map<String, dynamic>? location;
  final Map<String, dynamic>? schedule;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _InfoCard(
            icon: Icons.place_rounded,
            title: 'Lokasi',
            value: location?['name']?.toString() ?? '-',
            subtitle: 'Radius ${location?['radius']?.toString() ?? '-'} m',
            color: const Color(0xFF11CDEF),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _InfoCard(
            icon: Icons.schedule_rounded,
            title: 'Jadwal',
            value: schedule?['name']?.toString() ?? '-',
            subtitle:
                '${_timeValue(schedule?['check_in_time'])} - ${_timeValue(schedule?['check_out_time'])}',
            color: const Color(0xFFFB6340),
          ),
        ),
      ],
    );
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({
    required this.icon,
    required this.title,
    required this.value,
    required this.subtitle,
    required this.color,
  });

  final IconData icon;
  final String title;
  final String value;
  final String subtitle;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: _surface,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE9ECEF)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 34,
            height: 34,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(height: 12),
          Text(
            title,
            style: const TextStyle(
              color: _muted,
              fontSize: 12,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              color: _ink,
              fontSize: 16,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            subtitle,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(color: _muted, fontSize: 12),
          ),
        ],
      ),
    );
  }
}

class _SummaryStrip extends StatelessWidget {
  const _SummaryStrip({
    required this.presentCount,
    required this.lateCount,
    required this.earlyCount,
  });

  final int presentCount;
  final int lateCount;
  final int earlyCount;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: _surface,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE9ECEF)),
      ),
      child: Row(
        children: [
          Expanded(
            child: _SummaryItem(
              label: 'Hadir',
              value: presentCount.toString(),
              color: const Color(0xFF2DCE89),
            ),
          ),
          Expanded(
            child: _SummaryItem(
              label: 'Telat',
              value: lateCount.toString(),
              color: const Color(0xFFE85D04),
            ),
          ),
          Expanded(
            child: _SummaryItem(
              label: 'Cepat',
              value: earlyCount.toString(),
              color: const Color(0xFFDD2D4A),
            ),
          ),
        ],
      ),
    );
  }
}

class _SummaryItem extends StatelessWidget {
  const _SummaryItem({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(
          value,
          style: TextStyle(
            color: color,
            fontSize: 22,
            fontWeight: FontWeight.w900,
          ),
        ),
        Text(
          label,
          style: const TextStyle(
            color: _muted,
            fontSize: 12,
            fontWeight: FontWeight.w700,
          ),
        ),
      ],
    );
  }
}

class _HistorySection extends StatelessWidget {
  const _HistorySection({required this.history});

  final List<Map<String, dynamic>> history;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                'Riwayat Absensi',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  color: _ink,
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
            Text(
              '${history.length} data',
              style: const TextStyle(
                color: _muted,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        if (history.isEmpty)
          const _EmptyHistory()
        else
          ...history.map((attendance) => _HistoryTile(attendance: attendance)),
      ],
    );
  }
}

class _HistoryTile extends StatelessWidget {
  const _HistoryTile({required this.attendance});

  final Map<String, dynamic> attendance;

  @override
  Widget build(BuildContext context) {
    final color = _statusColor(attendance);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: _surface,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE9ECEF)),
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Icon(Icons.event_available_rounded, color: color, size: 22),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _dateLabel(attendance['date'] as String?),
                  style: const TextStyle(
                    color: _ink,
                    fontSize: 16,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 5),
                Text(
                  '${_timeValue(attendance['check_in'])} masuk • ${_timeValue(attendance['check_out'])} pulang',
                  style: const TextStyle(color: _muted, fontSize: 13),
                ),
                if (attendance['distance_meters'] != null) ...[
                  const SizedBox(height: 3),
                  Text(
                    'Jarak ${attendance['distance_meters']} m',
                    style: const TextStyle(color: _muted, fontSize: 12),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(width: 10),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Text(
              _statusLabel(attendance),
              style: TextStyle(
                color: color,
                fontSize: 11,
                fontWeight: FontWeight.w900,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _EmptyHistory extends StatelessWidget {
  const _EmptyHistory();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: _surface,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE9ECEF)),
      ),
      child: const Column(
        children: [
          Icon(Icons.event_busy_rounded, color: _muted, size: 36),
          SizedBox(height: 10),
          Text(
            'Belum ada riwayat absensi',
            style: TextStyle(color: _ink, fontWeight: FontWeight.w800),
          ),
          SizedBox(height: 4),
          Text(
            'Data akan tampil setelah Anda absen.',
            style: TextStyle(color: _muted),
          ),
        ],
      ),
    );
  }
}

class _MessageBox extends StatelessWidget {
  const _MessageBox({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF4E5),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFFFC078)),
      ),
      child: Text(message, style: const TextStyle(color: Color(0xFF7A4B00))),
    );
  }
}

class ApiException implements Exception {
  ApiException(this.message);

  final String message;

  factory ApiException.fromPayload(Map<String, dynamic> payload) {
    final errors = payload['errors'];
    if (errors is Map && errors.isNotEmpty) {
      final first = errors.values.first;
      if (first is List && first.isNotEmpty) {
        return ApiException(first.first.toString());
      }
    }

    return ApiException(
      (payload['message'] ?? 'Terjadi kesalahan server.').toString(),
    );
  }

  @override
  String toString() => message;
}
