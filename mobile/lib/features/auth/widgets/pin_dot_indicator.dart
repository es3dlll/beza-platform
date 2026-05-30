import 'package:flutter/material.dart';

class PinDotIndicator extends StatelessWidget {
  final int totalDots;
  final int filledDots;
  final double dotSize;
  final Color fillColor;
  final Color emptyColor;

  const PinDotIndicator({
    super.key,
    this.totalDots = 6,
    this.filledDots = 0,
    this.dotSize = 16,
    this.fillColor = const Color(0xFF2E7D32),
    this.emptyColor = const Color(0xFFE0E0E0),
  });

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.ltr,
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: List.generate(totalDots, (index) {
          final isFilled = index < filledDots;
          return Container(
            width: dotSize,
            height: dotSize,
            margin: EdgeInsets.symmetric(horizontal: dotSize * 0.4),
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: isFilled ? fillColor : emptyColor,
              border: isFilled
                  ? null
                  : Border.all(color: const Color(0xFFBDBDBD), width: 1.5),
            ),
          );
        }),
      ),
    );
  }
}
