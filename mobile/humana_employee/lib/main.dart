import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';
import 'dart:ui' as ui;

import 'package:camera/camera.dart';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:google_mlkit_face_detection/google_mlkit_face_detection.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

const String apiBaseUrl = 'https://humana.ibnuapps.cloud/api/mobile';
const Color _primary = Color(0xFFcb0c9f);
const Color _ink = Color(0xFF27375F);
const Color _muted = Color(0xFF8392AB);
const Color _surface = Colors.white;

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
    if (_loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    if (_token == null) {
      return LoginPage(onLoggedIn: _setToken);
    }

    return AttendanceHomePage(
      token: _token!,
      onLoggedOut: () => _setToken(null),
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
  });

  final String token;
  final VoidCallback onLoggedOut;

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
    _refresh();
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

class AttendanceCameraPage extends StatefulWidget {
  const AttendanceCameraPage({super.key});

  @override
  State<AttendanceCameraPage> createState() => _AttendanceCameraPageState();
}

class _AttendanceCameraPageState extends State<AttendanceCameraPage> {
  CameraController? _controller;
  late final FaceDetector _faceDetector;
  bool _initializing = true;
  bool _capturing = false;
  String? _message;

  @override
  void initState() {
    super.initState();
    _faceDetector = FaceDetector(
      options: FaceDetectorOptions(
        performanceMode: FaceDetectorMode.accurate,
        enableContours: true,
      ),
    );
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
    final faces = await _faceDetector.processImage(
      InputImage.fromFilePath(file.path),
    );

    if (faces.isEmpty) {
      return 'Wajah tidak terdeteksi. Hadapkan wajah ke kamera.';
    }

    if (faces.length > 1) {
      return 'Terdeteksi lebih dari satu wajah.';
    }

    final face = faces.first.boundingBox;
    final width = image.width.toDouble();
    final height = image.height.toDouble();
    final centerX = face.left + face.width / 2;
    final centerY = face.top + face.height / 2;

    if (face.width < width * 0.24 || face.height < height * 0.22) {
      return 'Wajah terlalu jauh. Dekatkan wajah ke kamera.';
    }

    if (face.left < width * 0.04 ||
        face.right > width * 0.96 ||
        face.top < height * 0.04 ||
        face.bottom > height * 0.94) {
      return 'Wajah terpotong atau terlalu pinggir.';
    }

    if (centerX < width * 0.28 ||
        centerX > width * 0.72 ||
        centerY < height * 0.22 ||
        centerY > height * 0.72) {
      return 'Posisikan wajah di tengah frame.';
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
    _faceDetector.close();
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
