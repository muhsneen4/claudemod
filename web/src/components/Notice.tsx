import type { ReactNode } from 'react';

interface Props {
  tone?: 'info' | 'warn' | 'danger' | 'plain';
  icon?: string;
  title?: string;
  children: ReactNode;
}

export function Notice({ tone = 'plain', icon, title, children }: Props) {
  const className = tone === 'plain' ? 'notice' : `notice ${tone}`;
  return (
    <div className={className} role={tone === 'danger' ? 'alert' : undefined}>
      {icon && (
        <span aria-hidden="true" style={{ fontSize: 18, lineHeight: '22px' }}>
          {icon}
        </span>
      )}
      <div>
        {title && <b>{title}</b>}
        <div>{children}</div>
      </div>
    </div>
  );
}
