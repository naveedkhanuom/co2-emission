{{-- Scope 3 Calculator: match system layout (Bootstrap + app vars). Only category nav and small tweaks. --}}

/* Category nav: same look as app sidebar menu */
.scope3-cat-nav {
  list-style: none;
  padding: 0;
  margin: 0;
}
.scope3-cat-nav .scope3-cat-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  margin-bottom: 2px;
  border-radius: 0 30px 30px 0;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.9rem;
  color: var(--gray-800);
  border: 1px solid transparent;
}
.scope3-cat-nav .scope3-cat-item:hover {
  background-color: var(--gray-100);
  color: var(--primary-green);
}
.scope3-cat-nav .scope3-cat-item.active {
  background-color: var(--gray-100);
  color: var(--primary-green);
  border-color: rgba(46, 125, 50, 0.15);
}
.scope3-cat-nav .scope3-cat-item .cat-num {
  width: 26px;
  height: 26px;
  border-radius: 8px;
  background: var(--gray-200);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  flex-shrink: 0;
  color: var(--gray-600);
}
.scope3-cat-nav .scope3-cat-item.active .cat-num,
.scope3-cat-nav .scope3-cat-item.completed .cat-num {
  background: var(--primary-green);
  color: white;
}
.scope3-cat-nav .scope3-cat-item .cat-name { flex: 1; line-height: 1.3; }
.scope3-cat-nav .scope3-cat-item .check-mark {
  font-size: 0.75rem;
  color: var(--primary-green);
  opacity: 0;
}
.scope3-cat-nav .scope3-cat-item.completed .check-mark { opacity: 1; }

/* Summary total highlight (system green) */
.scope3-summary-total {
  text-align: center;
  padding: 2rem;
  background: linear-gradient(135deg, rgba(46, 125, 50, 0.06), rgba(46, 125, 50, 0.04));
  border: 1px solid rgba(46, 125, 50, 0.15);
  border-radius: 12px;
  margin-bottom: 1.5rem;
}
.scope3-summary-total .value { font-size: 2.5rem; font-weight: 800; color: var(--primary-green); }
.scope3-summary-total .unit { color: var(--gray-600); margin-top: 0.25rem; }

/* Bar and table in summary */
.scope3-summary-bar {
  display: flex;
  height: 12px;
  border-radius: 8px;
  overflow: hidden;
  gap: 2px;
  margin-bottom: 1rem;
}
.scope3-summary-bar-seg { height: 100%; border-radius: 6px; transition: width 0.5s ease; min-width: 4px; }
.scope3-summary-table .cat-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; display: inline-block; margin-right: 0.5rem; }
.scope3-equiv-item { text-align: center; padding: 1rem; }

/* Data entry rows: grid layout */
.scope3-dyn-row {
  display: grid;
  gap: 0.75rem;
  align-items: end;
  margin-bottom: 0.75rem;
  padding: 1rem;
  background: var(--gray-50);
  border-radius: 8px;
  border: 1px solid var(--gray-200);
}
.scope3-dyn-row .form-control, .scope3-dyn-row .form-select { font-size: 0.9rem; }
.scope3-dyn-row .mini-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gray-600); margin-bottom: 0.25rem; }
.scope3-ef-help {
  font-size: 0.75rem;
  color: var(--gray-600);
  margin-top: 0.35rem;
  padding: 0.35rem 0.5rem;
  background: rgba(46, 125, 50, 0.06);
  border-radius: 6px;
  border-left: 3px solid rgba(46, 125, 50, 0.4);
}
.scope3-ef-help i { color: var(--primary-green); }

/* Easy flow: 3 steps and “set once” card */
.scope3-steps-list {
  padding-left: 1.25rem;
  color: var(--gray-700);
}
.scope3-steps-list li { margin-bottom: 0.35rem; }
.scope3-save-options-card {
  border-left: 4px solid var(--primary-green);
  background: linear-gradient(135deg, rgba(46, 125, 50, 0.04), transparent);
}
.scope3-step-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: var(--primary-green);
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
}
.scope3-tip-inline {
  font-size: 0.85rem;
  color: var(--gray-600);
}
