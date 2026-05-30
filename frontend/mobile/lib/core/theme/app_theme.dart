import 'package:flutter/material.dart';

class AppTheme {
  AppTheme._();

  // Brand palette — Design Language 2026
  static const Color primary = Color(0xFF0D7C4A);
  static const Color primaryLight = Color(0xFF22A67E);
  static const Color primaryDark = Color(0xFF0A5E38);
  static const Color accent = Color(0xFFC8962E);
  static const Color accentLight = Color(0xFFE8D4A0);
  static const Color accentDark = Color(0xFFA67A1E);

  // Neutrals
  static const Color background = Color(0xFFF7F7F7);
  static const Color surface = Colors.white;
  static const Color surfaceVariant = Color(0xFFF0F0F0);
  static const Color surfaceContainerLow = Color(0xFFEDEDED);
  static const Color surfaceContainerHigh = Color(0xFFE5E5E5);

  // Semantic
  static const Color error = Color(0xFFD32F2F);
  static const Color errorLight = Color(0xFFFDE8E8);
  static const Color success = Color(0xFF22A67E);
  static const Color successLight = Color(0xFFD4F5E8);
  static const Color warning = Color(0xFFF5A623);
  static const Color warningLight = Color(0xFFFEF7E0);
  static const Color info = Color(0xFF2C6BED);
  static const Color infoLight = Color(0xFFDDE8FC);

  // Text
  static const Color textPrimary = Color(0xFF1A1A1A);
  static const Color textSecondary = Color(0xFF6B6B6B);
  static const Color textTertiary = Color(0xFF9E9E9E);
  static const Color textOnPrimary = Colors.white;
  static const Color textOnAccent = Color(0xFF3D2E00);

  // Borders
  static const Color divider = Color(0xFFE5E5E5);
  static const Color inputBorder = Color(0xFFD4D4D4);
  static const Color shimmer = Color(0xFFE5E5E5);

  static const Color chipBackground = Color(0xFFF0F0F0);

  // Spacing (4px grid)
  static const double spaceXxs = 2;
  static const double spaceXs = 4;
  static const double spaceSm = 8;
  static const double spaceMd = 12;
  static const double spaceLg = 16;
  static const double spaceXl = 24;
  static const double spaceXxl = 32;
  static const double spaceXxxl = 48;

  static const EdgeInsets screenPadding = EdgeInsets.symmetric(horizontal: 20);
  static const EdgeInsets cardPadding = EdgeInsets.all(16);
  static const EdgeInsets cardPaddingLg = EdgeInsets.all(20);
  static const EdgeInsets sectionPadding = EdgeInsets.symmetric(vertical: 16);

  // Border radius
  static BorderRadius get radiusSm => BorderRadius.circular(8);
  static BorderRadius get radiusMd => BorderRadius.circular(12);
  static BorderRadius get radiusLg => BorderRadius.circular(16);
  static BorderRadius get radiusXl => BorderRadius.circular(24);
  static BorderRadius get radiusXxl => BorderRadius.circular(32);
  static BorderRadius get radiusFull => BorderRadius.circular(100);

  // Shadow system
  static List<BoxShadow> get shadowSm => [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.04),
          blurRadius: 4,
          offset: const Offset(0, 1),
        ),
      ];

  static List<BoxShadow> get shadowMd => [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.06),
          blurRadius: 12,
          offset: const Offset(0, 4),
        ),
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.03),
          blurRadius: 4,
          offset: const Offset(0, 1),
        ),
      ];

  static List<BoxShadow> get shadowLg => [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.08),
          blurRadius: 24,
          offset: const Offset(0, 8),
        ),
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.04),
          blurRadius: 8,
          offset: const Offset(0, 2),
        ),
      ];

  static List<BoxShadow> get shadowXl => [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.12),
          blurRadius: 32,
          offset: const Offset(0, 12),
        ),
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.05),
          blurRadius: 8,
          offset: const Offset(0, 3),
        ),
      ];

  // Gradients
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [primary, Color(0xFF2E7D32)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient primaryGradientV = LinearGradient(
    colors: [primaryDark, primary, primaryLight],
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
  );

  static const LinearGradient accentGradient = LinearGradient(
    colors: [accent, accentDark],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient splashGradient = LinearGradient(
    colors: [Color(0xFF0D3B0F), Color(0xFF1B5E20), Color(0xFF2E7D32)],
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
  );

  static const LinearGradient cardGradient = LinearGradient(
    colors: [primaryDark, primary],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient surfaceGradient = LinearGradient(
    colors: [background, Colors.white],
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
  );

  // --- Component-specific styling helpers ---
  static BoxDecoration get cardDecoration => BoxDecoration(
        color: surface,
        borderRadius: radiusLg,
        boxShadow: shadowMd,
      );

  static BoxDecoration get cardDecorationElevated => BoxDecoration(
        color: surface,
        borderRadius: radiusLg,
        boxShadow: shadowLg,
      );

  static BoxDecoration get accentChip => BoxDecoration(
        color: accent.withValues(alpha: 0.12),
        borderRadius: radiusFull,
      );

  static BoxDecoration get primaryChip => BoxDecoration(
        color: primary.withValues(alpha: 0.1),
        borderRadius: radiusFull,
      );

  static Decoration get navIndicator => BoxDecoration(
        gradient: primaryGradient,
        borderRadius: radiusFull,
        boxShadow: [
          BoxShadow(
            color: primary.withValues(alpha: 0.3),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      );

  static BoxDecoration get iconContainer => BoxDecoration(
        color: primary.withValues(alpha: 0.1),
        borderRadius: radiusMd,
      );

  static BoxDecoration get gradientIconContainer => BoxDecoration(
        gradient: primaryGradient,
        borderRadius: radiusMd,
        boxShadow: [
          BoxShadow(
            color: primary.withValues(alpha: 0.25),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      );

  static BoxDecoration get dividerDecoration => BoxDecoration(
        border: Border(
          bottom: BorderSide(color: divider, width: 1),
        ),
      );

  // --- Full ThemeData ---
  static ThemeData get light => ThemeData(
        useMaterial3: true,
        brightness: Brightness.light,
        scaffoldBackgroundColor: background,
        fontFamily: 'Cairo',
        colorScheme: const ColorScheme.light(
          primary: primary,
          onPrimary: textOnPrimary,
          secondary: accent,
          onSecondary: textOnAccent,
          surface: surface,
          onSurface: textPrimary,
          error: error,
          onError: Colors.white,
          outline: inputBorder,
        ),
        appBarTheme: const AppBarTheme(
          backgroundColor: Colors.transparent,
          foregroundColor: textPrimary,
          elevation: 0,
          centerTitle: true,
          scrolledUnderElevation: 0,
          titleTextStyle: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 18,
            fontWeight: FontWeight.w600,
            color: textPrimary,
          ),
        ),
        bottomNavigationBarTheme: BottomNavigationBarThemeData(
          backgroundColor: surface,
          selectedItemColor: primary,
          unselectedItemColor: textTertiary,
          type: BottomNavigationBarType.fixed,
          elevation: 8,
          selectedLabelStyle: const TextStyle(
            fontFamily: 'Cairo',
            fontSize: 11,
            fontWeight: FontWeight.w600,
          ),
          unselectedLabelStyle: const TextStyle(
            fontFamily: 'Cairo',
            fontSize: 11,
          ),
          selectedIconTheme: const IconThemeData(size: 24),
          unselectedIconTheme: const IconThemeData(size: 22),
        ),
        navigationBarTheme: NavigationBarThemeData(
          backgroundColor: surface,
          indicatorColor: Colors.transparent,
          labelTextStyle: WidgetStateProperty.resolveWith((states) {
            if (states.contains(WidgetState.selected)) {
              return const TextStyle(
                fontFamily: 'Cairo',
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: primary,
              );
            }
            return const TextStyle(
              fontFamily: 'Cairo',
              fontSize: 11,
              color: textTertiary,
            );
          }),
          iconTheme: WidgetStateProperty.resolveWith((states) {
            if (states.contains(WidgetState.selected)) {
              return const IconThemeData(color: primary, size: 24);
            }
            return const IconThemeData(color: textTertiary, size: 22);
          }),
        ),
        cardTheme: CardThemeData(
          elevation: 0,
          color: surface,
          shadowColor: Colors.black.withValues(alpha: 0.06),
          shape: RoundedRectangleBorder(borderRadius: radiusLg),
          margin: const EdgeInsets.symmetric(horizontal: 0, vertical: 4),
        ),
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: primary,
            foregroundColor: textOnPrimary,
            minimumSize: const Size(double.infinity, 52),
            shape: RoundedRectangleBorder(borderRadius: radiusMd),
            textStyle: const TextStyle(
              fontFamily: 'Cairo',
              fontSize: 16,
              fontWeight: FontWeight.w600,
            ),
            elevation: 0,
            shadowColor: Colors.transparent,
            padding: const EdgeInsets.symmetric(horizontal: 24),
          ),
        ),
        outlinedButtonTheme: OutlinedButtonThemeData(
          style: OutlinedButton.styleFrom(
            foregroundColor: primary,
            side: const BorderSide(color: primary, width: 1.5),
            minimumSize: const Size(double.infinity, 52),
            shape: RoundedRectangleBorder(borderRadius: radiusMd),
            textStyle: const TextStyle(
              fontFamily: 'Cairo',
              fontSize: 16,
              fontWeight: FontWeight.w600,
            ),
            padding: const EdgeInsets.symmetric(horizontal: 24),
          ),
        ),
        textButtonTheme: TextButtonThemeData(
          style: TextButton.styleFrom(
            foregroundColor: primary,
            textStyle: const TextStyle(
              fontFamily: 'Cairo',
              fontSize: 15,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: surfaceVariant,
          border: OutlineInputBorder(
            borderRadius: radiusMd,
            borderSide: BorderSide.none,
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: radiusMd,
            borderSide: BorderSide.none,
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: radiusMd,
            borderSide: const BorderSide(color: primary, width: 2),
          ),
          errorBorder: OutlineInputBorder(
            borderRadius: radiusMd,
            borderSide: const BorderSide(color: error),
          ),
          focusedErrorBorder: OutlineInputBorder(
            borderRadius: radiusMd,
            borderSide: const BorderSide(color: error, width: 2),
          ),
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          labelStyle: const TextStyle(
            fontFamily: 'Cairo',
            color: textSecondary,
            fontSize: 14,
          ),
          hintStyle: TextStyle(
            fontFamily: 'Cairo',
            color: textTertiary,
            fontSize: 14,
          ),
        ),
        switchTheme: SwitchThemeData(
          thumbColor: WidgetStateProperty.resolveWith((states) {
            if (states.contains(WidgetState.selected)) return primary;
            return Colors.grey[400];
          }),
          trackColor: WidgetStateProperty.resolveWith((states) {
            if (states.contains(WidgetState.selected)) return primary.withValues(alpha: 0.3);
            return Colors.grey[300];
          }),
        ),
        checkboxTheme: CheckboxThemeData(
          fillColor: WidgetStateProperty.resolveWith((states) {
            if (states.contains(WidgetState.selected)) return primary;
            return Colors.transparent;
          }),
          shape: RoundedRectangleBorder(borderRadius: radiusSm),
        ),
        chipTheme: ChipThemeData(
          backgroundColor: chipBackground,
          selectedColor: primary.withValues(alpha: 0.15),
          labelStyle: const TextStyle(
            fontFamily: 'Cairo',
            fontSize: 13,
            color: textPrimary,
          ),
          secondaryLabelStyle: const TextStyle(
            fontFamily: 'Cairo',
            fontSize: 13,
            color: textOnPrimary,
          ),
          shape: RoundedRectangleBorder(borderRadius: radiusFull),
          side: BorderSide.none,
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        ),
        dialogTheme: DialogThemeData(
          backgroundColor: surface,
          shape: RoundedRectangleBorder(borderRadius: radiusXl),
          titleTextStyle: const TextStyle(
            fontFamily: 'Cairo',
            fontSize: 20,
            fontWeight: FontWeight.bold,
            color: textPrimary,
          ),
          contentTextStyle: const TextStyle(
            fontFamily: 'Cairo',
            fontSize: 14,
            color: textSecondary,
            height: 1.5,
          ),
        ),
        bottomSheetTheme: const BottomSheetThemeData(
          backgroundColor: surface,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.only(
              topLeft: Radius.circular(24),
              topRight: Radius.circular(24),
            ),
          ),
        ),
        snackBarTheme: SnackBarThemeData(
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: radiusMd),
          contentTextStyle: const TextStyle(
            fontFamily: 'Cairo',
            color: Colors.white,
            fontSize: 14,
          ),
          actionTextColor: accent,
        ),
        dividerTheme: const DividerThemeData(
          color: divider,
          thickness: 1,
          space: 1,
        ),
        progressIndicatorTheme: const ProgressIndicatorThemeData(
          color: primary,
          linearTrackColor: Color(0xFFE0E0E0),
        ),
        textTheme: const TextTheme(
          displayLarge: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 34,
            fontWeight: FontWeight.bold,
            color: textPrimary,
            height: 1.15,
          ),
          displayMedium: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 28,
            fontWeight: FontWeight.bold,
            color: textPrimary,
            height: 1.2,
          ),
          headlineLarge: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 24,
            fontWeight: FontWeight.bold,
            color: textPrimary,
            height: 1.25,
          ),
          headlineMedium: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 20,
            fontWeight: FontWeight.w600,
            color: textPrimary,
            height: 1.3,
          ),
          titleLarge: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 18,
            fontWeight: FontWeight.w600,
            color: textPrimary,
            height: 1.3,
          ),
          titleMedium: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 16,
            fontWeight: FontWeight.w500,
            color: textPrimary,
            height: 1.4,
          ),
          titleSmall: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 14,
            fontWeight: FontWeight.w600,
            color: textSecondary,
            height: 1.4,
          ),
          bodyLarge: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 16,
            color: textPrimary,
            height: 1.6,
          ),
          bodyMedium: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 14,
            color: textSecondary,
            height: 1.6,
          ),
          bodySmall: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 12,
            color: textTertiary,
            height: 1.5,
          ),
          labelLarge: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: textPrimary,
          ),
          labelMedium: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: textSecondary,
          ),
          labelSmall: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 12,
            fontWeight: FontWeight.w500,
            color: textTertiary,
          ),
        ),
      );

  static ThemeData get dark => ThemeData(
        useMaterial3: true,
        brightness: Brightness.dark,
        fontFamily: 'Cairo',
        scaffoldBackgroundColor: const Color(0xFF121212),
        colorScheme: const ColorScheme.dark(
          primary: primaryLight,
          onPrimary: Color(0xFF003300),
          secondary: accent,
          onSecondary: textOnAccent,
          surface: Color(0xFF1E1E1E),
          onSurface: Color(0xFFF5F5F5),
          error: Color(0xFFEF5350),
          onError: Colors.white,
          outline: Color(0xFF424242),
        ),
        appBarTheme: const AppBarTheme(
          backgroundColor: Colors.transparent,
          foregroundColor: Colors.white,
          elevation: 0,
          centerTitle: true,
          scrolledUnderElevation: 0,
        ),
        cardTheme: CardThemeData(
          elevation: 0,
          color: const Color(0xFF2C2C2C),
          shape: RoundedRectangleBorder(borderRadius: radiusLg),
        ),
        textTheme: const TextTheme(
          displayLarge: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 34,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
          bodyLarge: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 16,
            color: Color(0xFFE0E0E0),
          ),
        ),
      );
}
