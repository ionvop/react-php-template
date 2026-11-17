import { useEffect, useRef, type ReactNode } from "react";

interface DialogProps {
  open: boolean;
  onClose: () => void;
  children: ReactNode;
}

/**
 * Thin wrapper around the native <dialog> element so daisyUI's modal styles
 * work with React state instead of imperative DOM calls.
 */
export function Dialog({ open, onClose, children }: DialogProps) {
  const ref = useRef<HTMLDialogElement>(null);

  useEffect(() => {
    const element = ref.current;

    if (element === null) {
      return;
    }

    if (open && element.open === false) {
      element.showModal();
    }

    if (open === false && element.open) {
      element.close();
    }
  }, [open]);

  return (
    <dialog ref={ref} className="modal" onClose={onClose}>
      {children}
    </dialog>
  );
}