import 'package:flutter/material.dart';

class PhoneNumberInput extends StatefulWidget {
  final TextEditingController controller;
  final void Function(String)? onChanged;
  final String? errorText;

  const PhoneNumberInput({
    super.key,
    required this.controller,
    this.onChanged,
    this.errorText,
  });

  @override
  State<PhoneNumberInput> createState() => _PhoneNumberInputState();
}

class _PhoneNumberInputState extends State<PhoneNumberInput> {
  String _selectedCode = '+963';
  final List<String> _countryCodes = [
    '+963',
    '+966',
    '+971',
    '+974',
    '+965',
    '+962',
    '+961',
    '+970',
    '+90',
    '+44',
    '+1',
    '+49',
    '+33',
  ];

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: widget.errorText != null
                  ? Colors.red
                  : const Color(0xFFE0E0E0),
              width: 1.5,
            ),
            color: const Color(0xFFF9F9F9),
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8),
                child: DropdownButton<String>(
                  value: _selectedCode,
                  underline: const SizedBox(),
                  icon: const Icon(
                    Icons.arrow_drop_down,
                    color: Color(0xFF2E7D32),
                  ),
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF212121),
                    fontFamily: 'NotoNaskhArabic',
                  ),
                  items: _countryCodes.map((code) {
                    return DropdownMenuItem(
                      value: code,
                      child: Text(code),
                    );
                  }).toList(),
                  onChanged: (value) {
                    if (value != null) {
                      setState(() => _selectedCode = value);
                      widget.onChanged?.call(_selectedCode + widget.controller.text);
                    }
                  },
                ),
              ),
              Container(
                width: 1,
                height: 30,
                color: const Color(0xFFE0E0E0),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: TextField(
                  controller: widget.controller,
                  keyboardType: TextInputType.phone,
                  maxLength: 15,
                  textDirection: TextDirection.ltr,
                  textAlign: TextAlign.left,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w500,
                    letterSpacing: 1.2,
                    color: Color(0xFF212121),
                  ),
                  decoration: InputDecoration(
                    counterText: '',
                    border: InputBorder.none,
                    hintText: '09XXXXXXXX',
                    hintStyle: TextStyle(
                      color: const Color(0xFFBDBDBD),
                      fontFamily: 'NotoNaskhArabic',
                    ),
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 18,
                    ),
                  ),
                  onChanged: (value) {
                    if (value.length <= 15) {
                      widget.onChanged?.call(_selectedCode + value);
                    }
                  },
                ),
              ),
            ],
          ),
        ),
        if (widget.errorText != null)
          Padding(
            padding: const EdgeInsets.only(top: 8, right: 4),
            child: Row(
              children: [
                const Icon(Icons.error_outline, size: 16, color: Colors.red),
                const SizedBox(width: 6),
                Text(
                  widget.errorText!,
                  style: const TextStyle(
                    color: Colors.red,
                    fontSize: 13,
                    fontFamily: 'NotoNaskhArabic',
                  ),
                ),
              ],
            ),
          ),
      ],
    );
  }

  String get fullPhone => _selectedCode + widget.controller.text;
}
