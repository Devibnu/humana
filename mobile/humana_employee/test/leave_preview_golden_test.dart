import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:humana_employee/main.dart';

void _testNoop() {}

void main() {
  testWidgets('leave preview page matches phone layout', (tester) async {
    final view = tester.view;

    view.devicePixelRatio = 3.0;
    view.physicalSize = const Size(1290, 2796);

    addTearDown(() {
      view.resetDevicePixelRatio();
      view.resetPhysicalSize();
    });

    await tester.pumpWidget(
      const MaterialApp(
        home: LeaveRequestPage(
          token: 'preview-token',
          onLoggedOut: _testNoop,
          previewMode: true,
        ),
      ),
    );

    await tester.pumpAndSettle();

    expect(find.text('Ajukan Cuti / Izin'), findsOneWidget);
    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile('goldens/leave_preview.png'),
    );
  });
}