export default function Card({
  title,
  children,
  className = "",
}: {
  title?: string;
  children: React.ReactNode;
  className?: string;
}) {
  return (
    <div
      className={`rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800 dark:text-white ${className}`}
    >
      {title && (
        <h2 className="mb-3 text-sm font-semibold text-gray-600 dark:text-gray-300">
          {title}
        </h2>
      )}
      {children}
    </div>
  );
}
