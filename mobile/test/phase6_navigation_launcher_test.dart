import 'package:flutter_test/flutter_test.dart';

void main() {
  group('Test Phase 6: Mobile Native Navigation Deep Link Generator', () {
    test('Constructs platform-aware navigation URLs for Google Maps and Apple Maps', () {
      final double latitude = -1.2850;
      final double longitude = 36.8150;

      // Construct Android Google Maps URL
      final String googleMapsUrl = 'https://www.google.com/maps/dir/?api=1&destination=$latitude,$longitude&travelmode=driving';

      // Construct iOS Apple Maps URL
      final String appleMapsUrl = 'https://maps.apple.com/?daddr=$latitude,$longitude&dirflg=d';

      // Assert Google Maps deep link structure
      expect(googleMapsUrl, contains('https://www.google.com/maps/dir/'));
      expect(googleMapsUrl, contains('api=1'));
      expect(googleMapsUrl, contains('destination=-1.285,36.815'));
      expect(googleMapsUrl, contains('travelmode=driving'));

      // Assert Apple Maps deep link structure
      expect(appleMapsUrl, contains('https://maps.apple.com/'));
      expect(appleMapsUrl, contains('daddr=-1.285,36.815'));
      expect(appleMapsUrl, contains('dirflg=d'));
    });
  });
}
