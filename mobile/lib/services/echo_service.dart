import 'package:laravel_echo/laravel_echo.dart';
import 'package:pusher_client/pusher_client.dart';

class EchoService {
  static Echo? _echo;

  static Echo? get instance => _echo;

  /**
   * Initialize Laravel Echo configured for Laravel Reverb
   */
  static void initEcho({
    required String token,
    required String tenantId,
    String localIp = '192.168.2.54',
    String reverbPort = '8085',
    String apiPort = '8888',
  }) {
    // Configure Pusher Options specifically tuned for Laravel Reverb
    final options = PusherOptions(
      host: localIp,
      port: int.parse(reverbPort),
      encrypted: false,
      cluster: 'mt1', // Reverb uses mt1 cluster defaults
      activityTimeout: 120000,
      auth: PusherAuth(
        'http://$localIp:$apiPort/broadcasting/auth',
        headers: {
          'Authorization': 'Bearer $token',
          'Host': 'acme.lvh.me', // Force matching tenant domain context
          'Accept': 'application/json',
        },
      ),
    );

    // Instantiate Pusher client using Laravel Reverb credentials
    final pusher = PusherClient(
      'r0f7nxdu29eeukhdtfua', // App Key resolved from backend .env
      options,
      autoConnect: false,
    );

    _echo = Echo(
      client: pusher,
      broadcaster: EchoBroadcaster.pusher,
    );

    pusher.connect();
    
    print('Laravel Echo Reverb Connection Initialized.');
  }

  /**
   * Disconnect and clean up Echo connection session
   */
  static void disconnect() {
    _echo?.disconnect();
    _echo = null;
    print('Laravel Echo Session Disconnected.');
  }
}
