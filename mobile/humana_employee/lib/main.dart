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

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const HumanaEmployeeApp());
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
        scaffoldBackgroundColor: const Color(0xFFF8F9FA),
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

    return Scaffold(
      appBar: AppBar(
        title: const Text('Absensi Karyawan'),
        actions: [
          IconButton(onPressed: _refresh, icon: const Icon(Icons.refresh)),
          IconButton(onPressed: _logout, icon: const Icon(Icons.logout)),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _refresh,
              child: ListView(
                padding: const EdgeInsets.all(18),
                children: [
                  _HeroCard(
                    employeeName: employee?['name'] as String? ?? '-',
                    locationName:
                        location?['name'] as String? ?? 'Lokasi belum diatur',
                    scheduleName:
                        schedule?['name'] as String? ?? 'Jadwal belum diatur',
                    checkIn: attendance?['check_in'] as String?,
                    checkOut: attendance?['check_out'] as String?,
                    nextAction: nextAction,
                    submitting: _submitting,
                    onSubmit: isComplete || _submitting
                        ? null
                        : _submitAttendance,
                  ),
                  if (_message != null) ...[
                    const SizedBox(height: 12),
                    _MessageBox(message: _message!),
                  ],
                  const SizedBox(height: 18),
                  Text(
                    'Riwayat Absensi',
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 10),
                  ..._history.map(
                    (item) =>
                        _HistoryTile(attendance: item as Map<String, dynamic>),
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

class _HeroCard extends StatelessWidget {
  const _HeroCard({
    required this.employeeName,
    required this.locationName,
    required this.scheduleName,
    required this.checkIn,
    required this.checkOut,
    required this.nextAction,
    required this.submitting,
    required this.onSubmit,
  });

  final String employeeName;
  final String locationName;
  final String scheduleName;
  final String? checkIn;
  final String? checkOut;
  final String? nextAction;
  final bool submitting;
  final VoidCallback? onSubmit;

  @override
  Widget build(BuildContext context) {
    final label = nextAction == 'check_out'
        ? 'Absen Pulang'
        : nextAction == 'complete'
        ? 'Absensi Lengkap'
        : 'Absen Masuk';

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        gradient: const LinearGradient(
          colors: [Color(0xFF15224C), Color(0xFFcb0c9f)],
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            employeeName,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 24,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 6),
          Text(locationName, style: const TextStyle(color: Colors.white70)),
          Text(scheduleName, style: const TextStyle(color: Colors.white70)),
          const SizedBox(height: 18),
          Row(
            children: [
              Expanded(
                child: _TimeBox(title: 'Masuk', value: checkIn ?? '-'),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _TimeBox(title: 'Pulang', value: checkOut ?? '-'),
              ),
            ],
          ),
          const SizedBox(height: 18),
          SizedBox(
            width: double.infinity,
            height: 52,
            child: FilledButton.icon(
              style: FilledButton.styleFrom(
                backgroundColor: Colors.white,
                foregroundColor: const Color(0xFF15224C),
              ),
              onPressed: onSubmit,
              icon: submitting
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.camera_alt),
              label: Text(submitting ? 'Menyimpan...' : label),
            ),
          ),
        ],
      ),
    );
  }
}

class _TimeBox extends StatelessWidget {
  const _TimeBox({required this.title, required this.value});

  final String title;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(color: Colors.white70, fontSize: 12),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _HistoryTile extends StatelessWidget {
  const _HistoryTile({required this.attendance});

  final Map<String, dynamic> attendance;

  @override
  Widget build(BuildContext context) {
    final status = attendance['status'] as String? ?? '-';
    return Card(
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        title: Text(attendance['date'] as String? ?? '-'),
        subtitle: Text(
          'Masuk ${attendance['check_in'] ?? '-'} | Pulang ${attendance['check_out'] ?? '-'}',
        ),
        trailing: Chip(label: Text(status.toUpperCase())),
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
