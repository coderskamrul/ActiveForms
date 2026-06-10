/**
 * Lightweight toast notification system via context.
 */
import React, { createContext, useContext, useState, useCallback } from 'react';

const ToastContext = createContext({ notify: () => {} });

/**
 * Access the toast notifier.
 * @returns {{notify: function}}
 */
export const useToast = () => useContext(ToastContext);

/**
 * Provider rendering the toast stack.
 * @param {{children: React.ReactNode}} props Props.
 */
export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([]);

  const notify = useCallback((message, type = 'success') => {
    const id = Math.random().toString(36).slice(2);
    setToasts((prev) => [...prev, { id, message, type }]);
    setTimeout(() => {
      setToasts((prev) => prev.filter((tst) => tst.id !== id));
    }, 3200);
  }, []);

  return (
    <ToastContext.Provider value={{ notify }}>
      {children}
      <div className="easyforms-toasts">
        {toasts.map((tst) => (
          <div key={tst.id} className={`easyforms-toast easyforms-toast--${tst.type}`}>{tst.message}</div>
        ))}
      </div>
    </ToastContext.Provider>
  );
}
