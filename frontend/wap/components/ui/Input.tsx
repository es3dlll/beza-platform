import { InputHTMLAttributes } from "react";

export default function Input(
  props: InputHTMLAttributes<HTMLInputElement> & { label?: string }
) {
  const { label, className = "", ...rest } = props;
  return (
    <div className="space-y-1">
      {label && (
        <label className="block text-sm text-gray-600 dark:text-gray-300">
          {label}
        </label>
      )}
      <input
        {...rest}
        className={`w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white ${className}`}
      />
    </div>
  );
}
