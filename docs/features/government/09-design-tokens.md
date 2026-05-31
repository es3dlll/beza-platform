# Government Collections Design Tokens

## Color Tokens

### Government-Specific Colors
```dart
class GovernmentColors {
  // Service category colours
  static const taxPrimary = Color(0xFFE74C3C);          // Red — taxes
  static const vehiclePrimary = Color(0xFF3498DB);      // Blue — vehicles
  static const passportPrimary = Color(0xFF2ECC71);     // Green — passports
  static const universityPrimary = Color(0xFF9B59B6);   // Purple — education
  static const courtPrimary = Color(0xFFF39C12);        // Orange — judiciary
  static const municipalityPrimary = Color(0xFF1ABC9C); // Teal — municipal
  static const civilRegistryPrimary = Color(0xFF34495E); // Dark grey — civil
  static const trafficFinePrimary = Color(0xFFE67E22);  // Dark orange — traffic

  // Status colours
  static const paymentSuccess = Color(0xFF27AE60);       // Green
  static const paymentPending = Color(0xFFF39C12);       // Amber
  static const paymentFailed = Color(0xFFE74C3C);        // Red
  static const paymentRefunded = Color(0xFF95A5A6);      // Grey
  static const overDue = Color(0xFFC0392B);              // Dark red
  static const dueSoon = Color(0xFFE67E22);              // Orange
}
```

### Ministry Badge Colours
```dart
static const ministryBadges = {
  'ministry_of_finance': Color(0xFF2C3E50),
  'ministry_of_interior': Color(0xFF1A5276),
  'ministry_of_education': Color(0xFF7D3C98),
  'ministry_of_justice': Color(0xFF935116),
  'ministry_of_transport': Color(0xFF1F618D),
};
```

## Typography
```dart
class GovernmentTypography {
  // Large amount display
  static const amountDisplay = TextStyle(
    fontFamily: 'BezaSans',
    fontSize: 36,
    fontWeight: FontWeight.w700,
    height: 1.1,
  );

  // Receipt reference
  static const receiptNumber = TextStyle(
    fontFamily: 'BezaMono',
    fontSize: 14,
    fontWeight: FontWeight.w500,
    letterSpacing: 1.2,
  );

  // Service name in hub grid
  static const serviceName = TextStyle(
    fontFamily: 'BezaSans',
    fontSize: 14,
    fontWeight: FontWeight.w600,
  );

  // Fee breakdown
  static const feeLabel = TextStyle(
    fontFamily: 'BezaSans',
    fontSize: 14,
    fontWeight: FontWeight.w400,
    color: Color(0xFF7F8C8D),
  );

  static const feeAmount = TextStyle(
    fontFamily: 'BezaSans',
    fontSize: 14,
    fontWeight: FontWeight.w600,
    color: Color(0xFF2C3E50),
  );

  // Total (emphasised)
  static const totalAmount = TextStyle(
    fontFamily: 'BezaSans',
    fontSize: 18,
    fontWeight: FontWeight.w700,
    color: Color(0xFFE74C3C),
  );

  // Receipt QR title
  static const qrTitle = TextStyle(
    fontFamily: 'BezaSans',
    fontSize: 12,
    fontWeight: FontWeight.w500,
    color: Color(0xFF7F8C8D),
  );
}
```

## Spacing
```dart
class GovernmentSpacing {
  static const screenPadding = EdgeInsets.all(16.0);
  static const cardPadding = EdgeInsets.all(16.0);
  static const gridGap = 12.0;
  static const sectionGap = 24.0;
  static const serviceIcon = 40.0;
}
```

## Icon System
```dart
class GovernmentIcons {
  static const serviceTax = '💰';           // Placeholder — will use SVG
  static const serviceVehicle = '🚗';
  static const servicePassport = '🛂';
  static const serviceUniversity = '🎓';
  static const serviceCourt = '⚖️';
  static const serviceMunicipality = '🏛️';
  static const serviceCivilRegistry = '📜';
  static const serviceTrafficFine = '🚦';
  static const paymentReceipt = '📋';
  static const paymentSuccess = '✅';
  static const paymentFailed = '❌';
  static const qrCode = '🔲';
  static const share = '💬';
  static const download = '📥';
  static const calendarReminder = '⏰';
  static const shield = '🛡️';
}
```
