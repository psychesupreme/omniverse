import 'dart:async';
import 'package:flutter/material.dart';
import 'package:isar/isar.dart';
import 'package:path_provider/path_provider.dart';
import 'package:omniroute_mobile/collections/outlet.dart';
import 'package:omniroute_mobile/collections/tracking_log.dart';
import 'package:omniroute_mobile/services/location_service.dart';
import 'package:omniroute_mobile/services/session_service.dart';
import 'package:omniroute_mobile/views/login_screen.dart';
import 'package:omniroute_mobile/views/dashboard_screen.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize background location tracking services
  await LocationService.initializeService();

  final dir = await getApplicationDocumentsDirectory();
  final isar = await Isar.open(
    [OutletSchema, TrackingLogSchema],
    directory: dir.path,
  );

  runApp(MyApp(isar: isar));
}

class MyApp extends StatelessWidget {
  final Isar isar;

  const MyApp({super.key, required this.isar});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'OmniRoute',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.teal),
        useMaterial3: true,
      ),
      home: FutureBuilder<bool>(
        future: SessionService.hasSession(),
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Scaffold(
              body: Center(
                child: CircularProgressIndicator(),
              ),
            );
          }
          if (snapshot.data == true) {
            return DashboardScreen(isar: isar);
          } else {
            return LoginScreen(isar: isar);
          }
        },
      ),
    );
  }
}
