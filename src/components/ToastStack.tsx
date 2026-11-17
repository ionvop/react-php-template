import type { Toast } from "../state/useToasts";

const ALERT_CLASS: Record<Toast["kind"], string> = {
  success: "alert-success",
  error: "alert-error",
  info: "alert-info",
};

interface ToastStackProps {
  toasts: Toast[];
  onDismiss: (id: number) => void;
}

export function ToastStack({ toasts, onDismiss }: ToastStackProps) {
  if (toasts.length === 0) {
    return null;
  }

  return (
    <div className="toast toast-end toast-bottom z-50">
      {toasts.map((toast) => (
        <div key={toast.id} role="alert" className={`alert ${ALERT_CLASS[toast.kind]}`}>
          <span className="text-sm">{toast.message}</span>
          <button
            type="button"
            className="btn btn-ghost btn-xs"
            onClick={() => onDismiss(toast.id)}
            aria-label="Dismiss notification"
          >
            ✕
          </button>
        </div>
      ))}
    </div>
  );
}