/**
 * jQuery stub for tests.
 *
 * This provides a minimal jQuery-like API for tests that haven't been
 * fully migrated away from jQuery yet. The actual source code no longer
 * uses jQuery.
 */

type JQueryElement = {
  length: number;
  [index: number]: HTMLElement | undefined;
  get(index?: number): HTMLElement | HTMLElement[] | undefined;
  each(callback: (index: number, element: HTMLElement) => void): JQueryElement;
  find(selector: string): JQueryElement;
  parent(): JQueryElement;
  parents(selector?: string): JQueryElement;
  closest(selector: string): JQueryElement;
  children(selector?: string): JQueryElement;
  siblings(selector?: string): JQueryElement;
  first(): JQueryElement;
  last(): JQueryElement;
  eq(index: number): JQueryElement;
  filter(selector: string | ((index: number, element: HTMLElement) => boolean)): JQueryElement;
  not(selector: string): JQueryElement;
  is(selector: string): boolean;
  has(selector: string): JQueryElement;
  add(selector: string | HTMLElement): JQueryElement;
  contents(): JQueryElement;

  // DOM manipulation
  html(): string;
  html(content: string): JQueryElement;
  text(): string;
  text(content: string): JQueryElement;
  val(): string;
  val(value: string | number): JQueryElement;
  attr(name: string): string | undefined;
  attr(name: string, value: string | number | null): JQueryElement;
  removeAttr(name: string): JQueryElement;
  prop(name: string): unknown;
  prop(name: string, value: unknown): JQueryElement;
  data(key: string): unknown;
  data(key: string, value: unknown): JQueryElement;
  addClass(className: string): JQueryElement;
  removeClass(className: string): JQueryElement;
  toggleClass(className: string, state?: boolean): JQueryElement;
  hasClass(className: string): boolean;
  css(property: string): string;
  css(property: string, value: string | number): JQueryElement;
  css(properties: Record<string, string | number>): JQueryElement;
  width(): number;
  width(value: number | string): JQueryElement;
  height(): number;
  height(value: number | string): JQueryElement;
  show(): JQueryElement;
  hide(): JQueryElement;
  toggle(state?: boolean): JQueryElement;
  fadeIn(duration?: number, callback?: () => void): JQueryElement;
  fadeOut(duration?: number, callback?: () => void): JQueryElement;
  slideDown(duration?: number, callback?: () => void): JQueryElement;
  slideUp(duration?: number, callback?: () => void): JQueryElement;

  // DOM insertion
  append(content: string | HTMLElement | JQueryElement): JQueryElement;
  prepend(content: string | HTMLElement | JQueryElement): JQueryElement;
  after(content: string | HTMLElement | JQueryElement): JQueryElement;
  before(content: string | HTMLElement | JQueryElement): JQueryElement;
  appendTo(target: string | HTMLElement | JQueryElement): JQueryElement;
  prependTo(target: string | HTMLElement | JQueryElement): JQueryElement;
  insertAfter(target: string | HTMLElement | JQueryElement): JQueryElement;
  insertBefore(target: string | HTMLElement | JQueryElement): JQueryElement;
  wrap(wrapper: string | HTMLElement): JQueryElement;
  wrapAll(wrapper: string | HTMLElement): JQueryElement;
  wrapInner(wrapper: string | HTMLElement): JQueryElement;
  unwrap(): JQueryElement;
  clone(withDataAndEvents?: boolean): JQueryElement;
  remove(): JQueryElement;
  detach(): JQueryElement;
  empty(): JQueryElement;
  replaceWith(content: string | HTMLElement | JQueryElement): JQueryElement;
  replaceAll(target: string): JQueryElement;

  // Events
  on(event: string, handler: (e: Event) => void): JQueryElement;
  on(event: string, selector: string, handler: (e: Event) => void): JQueryElement;
  off(event?: string, handler?: (e: Event) => void): JQueryElement;
  one(event: string, handler: (e: Event) => void): JQueryElement;
  trigger(event: string | Event, data?: unknown): JQueryElement;
  triggerHandler(event: string, data?: unknown): unknown;
  click(handler?: (e: Event) => void): JQueryElement;
  dblclick(handler?: (e: Event) => void): JQueryElement;
  focus(handler?: (e: Event) => void): JQueryElement;
  blur(handler?: (e: Event) => void): JQueryElement;
  change(handler?: (e: Event) => void): JQueryElement;
  submit(handler?: (e: Event) => void): JQueryElement;
  keydown(handler?: (e: Event) => void): JQueryElement;
  keyup(handler?: (e: Event) => void): JQueryElement;
  keypress(handler?: (e: Event) => void): JQueryElement;
  mouseenter(handler?: (e: Event) => void): JQueryElement;
  mouseleave(handler?: (e: Event) => void): JQueryElement;
  hover(handlerIn: (e: Event) => void, handlerOut?: (e: Event) => void): JQueryElement;
  scroll(handler?: (e: Event) => void): JQueryElement;
  resize(handler?: (e: Event) => void): JQueryElement;
  ready(handler: () => void): JQueryElement;

  // Position
  offset(): { top: number; left: number } | undefined;
  offset(coordinates: { top: number; left: number }): JQueryElement;
  position(): { top: number; left: number };
  scrollTop(): number;
  scrollTop(value: number): JQueryElement;
  scrollLeft(): number;
  scrollLeft(value: number): JQueryElement;

  // Utilities
  toArray(): HTMLElement[];
  index(selector?: string | HTMLElement): number;
  map<T>(callback: (index: number, element: HTMLElement) => T): T[];
  slice(start: number, end?: number): JQueryElement;

  // Animation (stubs)
  animate(properties: Record<string, unknown>, duration?: number, callback?: () => void): JQueryElement;
  stop(clearQueue?: boolean, jumpToEnd?: boolean): JQueryElement;
  delay(duration: number): JQueryElement;
  finish(): JQueryElement;
};

function createJQueryElement(elements: HTMLElement[]): JQueryElement {
  const jq: JQueryElement = {
    length: elements.length,

    get(index?: number) {
      if (index === undefined) return elements;
      return elements[index];
    },

    each(callback) {
      elements.forEach((el, i) => callback(i, el));
      return jq;
    },

    find(selector) {
      const found: HTMLElement[] = [];
      elements.forEach(el => {
        found.push(...Array.from(el.querySelectorAll<HTMLElement>(selector)));
      });
      return createJQueryElement(found);
    },

    parent() {
      const parents: HTMLElement[] = [];
      elements.forEach(el => {
        if (el.parentElement && !parents.includes(el.parentElement)) {
          parents.push(el.parentElement);
        }
      });
      return createJQueryElement(parents);
    },

    parents(selector) {
      const allParents: HTMLElement[] = [];
      elements.forEach(el => {
        let parent = el.parentElement;
        while (parent) {
          if (!selector || parent.matches(selector)) {
            if (!allParents.includes(parent)) allParents.push(parent);
          }
          parent = parent.parentElement;
        }
      });
      return createJQueryElement(allParents);
    },

    closest(selector) {
      const found: HTMLElement[] = [];
      elements.forEach(el => {
        const closest = el.closest<HTMLElement>(selector);
        if (closest && !found.includes(closest)) found.push(closest);
      });
      return createJQueryElement(found);
    },

    children(selector) {
      const kids: HTMLElement[] = [];
      elements.forEach(el => {
        Array.from(el.children).forEach(child => {
          if (child instanceof HTMLElement) {
            if (!selector || child.matches(selector)) {
              kids.push(child);
            }
          }
        });
      });
      return createJQueryElement(kids);
    },

    siblings(selector) {
      const sibs: HTMLElement[] = [];
      elements.forEach(el => {
        if (el.parentElement) {
          Array.from(el.parentElement.children).forEach(child => {
            if (child instanceof HTMLElement && child !== el) {
              if (!selector || child.matches(selector)) {
                if (!sibs.includes(child)) sibs.push(child);
              }
            }
          });
        }
      });
      return createJQueryElement(sibs);
    },

    first() {
      return createJQueryElement(elements.slice(0, 1));
    },

    last() {
      return createJQueryElement(elements.slice(-1));
    },

    eq(index) {
      const el = elements[index];
      return createJQueryElement(el ? [el] : []);
    },

    filter(selector) {
      if (typeof selector === 'function') {
        return createJQueryElement(elements.filter((el, i) => selector(i, el)));
      }
      return createJQueryElement(elements.filter(el => el.matches(selector)));
    },

    not(selector) {
      return createJQueryElement(elements.filter(el => !el.matches(selector)));
    },

    is(selector) {
      return elements.some(el => el.matches(selector));
    },

    has(selector) {
      return createJQueryElement(elements.filter(el => el.querySelector(selector) !== null));
    },

    add(selectorOrElement) {
      if (typeof selectorOrElement === 'string') {
        return createJQueryElement([...elements, ...Array.from(document.querySelectorAll<HTMLElement>(selectorOrElement))]);
      }
      return createJQueryElement([...elements, selectorOrElement]);
    },

    contents() {
      return createJQueryElement(elements);
    },

    html(content?: string) {
      if (content === undefined) {
        return elements[0]?.innerHTML ?? '';
      }
      elements.forEach(el => { el.innerHTML = content; });
      return jq;
    },

    text(content?: string) {
      if (content === undefined) {
        return elements.map(el => el.textContent).join('');
      }
      elements.forEach(el => { el.textContent = content; });
      return jq;
    },

    val(value?: string | number) {
      if (value === undefined) {
        const el = elements[0];
        if (el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement || el instanceof HTMLSelectElement) {
          return el.value;
        }
        return '';
      }
      elements.forEach(el => {
        if (el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement || el instanceof HTMLSelectElement) {
          el.value = String(value);
        }
      });
      return jq;
    },

    attr(name: string, value?: string | number | null) {
      if (value === undefined) {
        return elements[0]?.getAttribute(name) ?? undefined;
      }
      elements.forEach(el => {
        if (value === null) {
          el.removeAttribute(name);
        } else {
          el.setAttribute(name, String(value));
        }
      });
      return jq;
    },

    removeAttr(name) {
      elements.forEach(el => el.removeAttribute(name));
      return jq;
    },

    prop(name: string, value?: unknown) {
      if (value === undefined) {
        return (elements[0] as Record<string, unknown>)?.[name];
      }
      elements.forEach(el => {
        (el as Record<string, unknown>)[name] = value;
      });
      return jq;
    },

    data(key: string, value?: unknown) {
      if (value === undefined) {
        return elements[0]?.dataset[key];
      }
      elements.forEach(el => {
        el.dataset[key] = String(value);
      });
      return jq;
    },

    addClass(className) {
      elements.forEach(el => el.classList.add(...className.split(/\s+/).filter(Boolean)));
      return jq;
    },

    removeClass(className) {
      elements.forEach(el => el.classList.remove(...className.split(/\s+/).filter(Boolean)));
      return jq;
    },

    toggleClass(className, state) {
      elements.forEach(el => {
        if (state === undefined) {
          el.classList.toggle(className);
        } else {
          el.classList.toggle(className, state);
        }
      });
      return jq;
    },

    hasClass(className) {
      return elements.some(el => el.classList.contains(className));
    },

    css(property: string | Record<string, string | number>, value?: string | number) {
      if (typeof property === 'object') {
        elements.forEach(el => {
          Object.entries(property).forEach(([k, v]) => {
            el.style.setProperty(k, String(v));
          });
        });
        return jq;
      }
      if (value === undefined) {
        return elements[0]?.style.getPropertyValue(property) ?? '';
      }
      elements.forEach(el => {
        el.style.setProperty(property, String(value));
      });
      return jq;
    },

    width(value?: number | string) {
      if (value === undefined) {
        return elements[0]?.offsetWidth ?? 0;
      }
      elements.forEach(el => {
        el.style.width = typeof value === 'number' ? `${value}px` : value;
      });
      return jq;
    },

    height(value?: number | string) {
      if (value === undefined) {
        return elements[0]?.offsetHeight ?? 0;
      }
      elements.forEach(el => {
        el.style.height = typeof value === 'number' ? `${value}px` : value;
      });
      return jq;
    },

    show() {
      elements.forEach(el => { el.style.display = ''; });
      return jq;
    },

    hide() {
      elements.forEach(el => { el.style.display = 'none'; });
      return jq;
    },

    toggle(state) {
      elements.forEach(el => {
        const shouldShow = state ?? el.style.display === 'none';
        el.style.display = shouldShow ? '' : 'none';
      });
      return jq;
    },

    fadeIn(duration, callback) {
      elements.forEach(el => { el.style.display = ''; });
      callback?.();
      return jq;
    },

    fadeOut(duration, callback) {
      elements.forEach(el => { el.style.display = 'none'; });
      callback?.();
      return jq;
    },

    slideDown(duration, callback) {
      elements.forEach(el => { el.style.display = ''; });
      callback?.();
      return jq;
    },

    slideUp(duration, callback) {
      elements.forEach(el => { el.style.display = 'none'; });
      callback?.();
      return jq;
    },

    append(content) {
      elements.forEach(el => {
        if (typeof content === 'string') {
          el.insertAdjacentHTML('beforeend', content);
        } else if (content instanceof HTMLElement) {
          el.appendChild(content);
        } else if ('length' in content) {
          for (let i = 0; i < content.length; i++) {
            const child = content[i];
            if (child) el.appendChild(child);
          }
        }
      });
      return jq;
    },

    prepend(content) {
      elements.forEach(el => {
        if (typeof content === 'string') {
          el.insertAdjacentHTML('afterbegin', content);
        } else if (content instanceof HTMLElement) {
          el.insertBefore(content, el.firstChild);
        }
      });
      return jq;
    },

    after(content) {
      elements.forEach(el => {
        if (typeof content === 'string') {
          el.insertAdjacentHTML('afterend', content);
        } else if (content instanceof HTMLElement) {
          el.parentNode?.insertBefore(content, el.nextSibling);
        }
      });
      return jq;
    },

    before(content) {
      elements.forEach(el => {
        if (typeof content === 'string') {
          el.insertAdjacentHTML('beforebegin', content);
        } else if (content instanceof HTMLElement) {
          el.parentNode?.insertBefore(content, el);
        }
      });
      return jq;
    },

    appendTo(target) {
      const targetEl = typeof target === 'string' ? document.querySelector(target) :
        target instanceof HTMLElement ? target : target[0];
      if (targetEl) {
        elements.forEach(el => targetEl.appendChild(el));
      }
      return jq;
    },

    prependTo(target) {
      const targetEl = typeof target === 'string' ? document.querySelector(target) :
        target instanceof HTMLElement ? target : target[0];
      if (targetEl) {
        elements.forEach(el => targetEl.insertBefore(el, targetEl.firstChild));
      }
      return jq;
    },

    insertAfter(target) {
      const targetEl = typeof target === 'string' ? document.querySelector(target) :
        target instanceof HTMLElement ? target : target[0];
      if (targetEl?.parentNode) {
        elements.forEach(el => targetEl.parentNode!.insertBefore(el, targetEl.nextSibling));
      }
      return jq;
    },

    insertBefore(target) {
      const targetEl = typeof target === 'string' ? document.querySelector(target) :
        target instanceof HTMLElement ? target : target[0];
      if (targetEl?.parentNode) {
        elements.forEach(el => targetEl.parentNode!.insertBefore(el, targetEl));
      }
      return jq;
    },

    wrap(wrapper) {
      elements.forEach(el => {
        const wrapperEl = typeof wrapper === 'string' ?
          document.createRange().createContextualFragment(wrapper).firstElementChild as HTMLElement :
          wrapper.cloneNode(true) as HTMLElement;
        el.parentNode?.insertBefore(wrapperEl, el);
        wrapperEl.appendChild(el);
      });
      return jq;
    },

    wrapAll(wrapper) {
      if (elements.length === 0) return jq;
      const wrapperEl = typeof wrapper === 'string' ?
        document.createRange().createContextualFragment(wrapper).firstElementChild as HTMLElement :
        wrapper;
      elements[0].parentNode?.insertBefore(wrapperEl, elements[0]);
      elements.forEach(el => wrapperEl.appendChild(el));
      return jq;
    },

    wrapInner(wrapper) {
      elements.forEach(el => {
        const wrapperEl = typeof wrapper === 'string' ?
          document.createRange().createContextualFragment(wrapper).firstElementChild as HTMLElement :
          wrapper.cloneNode(true) as HTMLElement;
        while (el.firstChild) {
          wrapperEl.appendChild(el.firstChild);
        }
        el.appendChild(wrapperEl);
      });
      return jq;
    },

    unwrap() {
      elements.forEach(el => {
        const parent = el.parentElement;
        if (parent && parent !== document.body) {
          parent.replaceWith(...Array.from(parent.childNodes));
        }
      });
      return jq;
    },

    clone(withDataAndEvents) {
      return createJQueryElement(elements.map(el => el.cloneNode(true) as HTMLElement));
    },

    remove() {
      elements.forEach(el => el.remove());
      return jq;
    },

    detach() {
      elements.forEach(el => el.remove());
      return jq;
    },

    empty() {
      elements.forEach(el => { el.innerHTML = ''; });
      return jq;
    },

    replaceWith(content) {
      elements.forEach(el => {
        if (typeof content === 'string') {
          el.outerHTML = content;
        } else if (content instanceof HTMLElement) {
          el.replaceWith(content);
        }
      });
      return jq;
    },

    replaceAll(target) {
      document.querySelectorAll(target).forEach((el, i) => {
        if (elements[i]) el.replaceWith(elements[i]);
      });
      return jq;
    },

    on(event: string, selectorOrHandler: string | ((e: Event) => void), handler?: (e: Event) => void) {
      if (typeof selectorOrHandler === 'function') {
        elements.forEach(el => el.addEventListener(event, selectorOrHandler));
      } else if (handler) {
        // Delegated event
        elements.forEach(el => {
          el.addEventListener(event, (e) => {
            const target = e.target as HTMLElement;
            if (target.matches(selectorOrHandler)) {
              handler.call(target, e);
            }
          });
        });
      }
      return jq;
    },

    off(event, handler) {
      if (event && handler) {
        elements.forEach(el => el.removeEventListener(event, handler));
      }
      return jq;
    },

    one(event, handler) {
      elements.forEach(el => el.addEventListener(event, handler, { once: true }));
      return jq;
    },

    trigger(event, data) {
      let eventObj: Event;
      if (typeof event === 'string') {
        // Use appropriate event types for common events
        if (event === 'click' || event === 'dblclick' || event === 'mousedown' || event === 'mouseup' ||
            event === 'mouseenter' || event === 'mouseleave' || event === 'mouseover' || event === 'mouseout') {
          eventObj = new MouseEvent(event, { bubbles: true, cancelable: true });
        } else if (event === 'keydown' || event === 'keyup' || event === 'keypress') {
          eventObj = new KeyboardEvent(event, { bubbles: true, cancelable: true });
        } else if (event === 'submit') {
          eventObj = new Event(event, { bubbles: true, cancelable: true });
        } else if (event === 'change' || event === 'input' || event === 'focus' || event === 'blur') {
          eventObj = new Event(event, { bubbles: true, cancelable: true });
        } else {
          eventObj = new CustomEvent(event, { detail: data, bubbles: true, cancelable: true });
        }
      } else {
        eventObj = event;
      }
      elements.forEach(el => el.dispatchEvent(eventObj));
      return jq;
    },

    triggerHandler(event, data) {
      const eventObj = new CustomEvent(event, { detail: data, bubbles: false });
      elements[0]?.dispatchEvent(eventObj);
      return undefined;
    },

    click(handler) {
      if (handler) {
        return jq.on('click', handler);
      }
      elements.forEach(el => el.click());
      return jq;
    },

    dblclick(handler) {
      if (handler) return jq.on('dblclick', handler);
      elements.forEach(el => el.dispatchEvent(new MouseEvent('dblclick')));
      return jq;
    },

    focus(handler) {
      if (handler) return jq.on('focus', handler);
      (elements[0] as HTMLElement)?.focus();
      return jq;
    },

    blur(handler) {
      if (handler) return jq.on('blur', handler);
      (elements[0] as HTMLElement)?.blur();
      return jq;
    },

    change(handler) {
      if (handler) return jq.on('change', handler);
      elements.forEach(el => el.dispatchEvent(new Event('change')));
      return jq;
    },

    submit(handler) {
      if (handler) return jq.on('submit', handler);
      elements.forEach(el => {
        if (el instanceof HTMLFormElement) el.submit();
      });
      return jq;
    },

    keydown(handler) {
      if (handler) return jq.on('keydown', handler);
      elements.forEach(el => el.dispatchEvent(new KeyboardEvent('keydown')));
      return jq;
    },

    keyup(handler) {
      if (handler) return jq.on('keyup', handler);
      elements.forEach(el => el.dispatchEvent(new KeyboardEvent('keyup')));
      return jq;
    },

    keypress(handler) {
      if (handler) return jq.on('keypress', handler);
      elements.forEach(el => el.dispatchEvent(new KeyboardEvent('keypress')));
      return jq;
    },

    mouseenter(handler) {
      if (handler) return jq.on('mouseenter', handler);
      elements.forEach(el => el.dispatchEvent(new MouseEvent('mouseenter')));
      return jq;
    },

    mouseleave(handler) {
      if (handler) return jq.on('mouseleave', handler);
      elements.forEach(el => el.dispatchEvent(new MouseEvent('mouseleave')));
      return jq;
    },

    hover(handlerIn, handlerOut) {
      jq.on('mouseenter', handlerIn);
      if (handlerOut) jq.on('mouseleave', handlerOut);
      return jq;
    },

    scroll(handler) {
      if (handler) return jq.on('scroll', handler);
      return jq;
    },

    resize(handler) {
      if (handler) return jq.on('resize', handler);
      return jq;
    },

    ready(handler) {
      if (document.readyState !== 'loading') {
        handler();
      } else {
        document.addEventListener('DOMContentLoaded', handler);
      }
      return jq;
    },

    offset(coordinates?: { top: number; left: number }) {
      if (coordinates) {
        elements.forEach(el => {
          el.style.position = 'absolute';
          el.style.top = `${coordinates.top}px`;
          el.style.left = `${coordinates.left}px`;
        });
        return jq;
      }
      const el = elements[0];
      if (!el) return undefined;
      const rect = el.getBoundingClientRect();
      return { top: rect.top + window.scrollY, left: rect.left + window.scrollX };
    },

    position() {
      const el = elements[0];
      return { top: el?.offsetTop ?? 0, left: el?.offsetLeft ?? 0 };
    },

    scrollTop(value?: number) {
      if (value === undefined) {
        return elements[0]?.scrollTop ?? 0;
      }
      elements.forEach(el => { el.scrollTop = value; });
      return jq;
    },

    scrollLeft(value?: number) {
      if (value === undefined) {
        return elements[0]?.scrollLeft ?? 0;
      }
      elements.forEach(el => { el.scrollLeft = value; });
      return jq;
    },

    toArray() {
      return [...elements];
    },

    index(selector) {
      if (!selector) {
        const el = elements[0];
        if (!el?.parentElement) return -1;
        return Array.from(el.parentElement.children).indexOf(el);
      }
      if (typeof selector === 'string') {
        return Array.from(document.querySelectorAll(selector)).indexOf(elements[0]);
      }
      return elements.indexOf(selector);
    },

    map(callback) {
      return elements.map((el, i) => callback(i, el));
    },

    slice(start, end) {
      return createJQueryElement(elements.slice(start, end));
    },

    animate(properties, duration, callback) {
      // Stub - just apply final styles immediately
      Object.entries(properties).forEach(([prop, val]) => {
        elements.forEach(el => {
          el.style.setProperty(prop, String(val));
        });
      });
      callback?.();
      return jq;
    },

    stop() { return jq; },
    delay() { return jq; },
    finish() { return jq; },
  };

  // Add numeric indices
  elements.forEach((el, i) => {
    (jq as Record<number, HTMLElement>)[i] = el;
  });

  return jq;
}

interface JQueryStatic {
  (selector: string | HTMLElement | Document | Window | NodeList | HTMLElement[] | (() => void)): JQueryElement;
  ajax(options: Record<string, unknown>): { done: (cb: (data: unknown) => void) => { fail: (cb: (err: unknown) => void) => void }; fail: (cb: (err: unknown) => void) => { done: (cb: (data: unknown) => void) => void } };
  get(url: string, data?: Record<string, unknown>, callback?: (data: unknown) => void): ReturnType<JQueryStatic['ajax']>;
  post(url: string, data?: Record<string, unknown>, callback?: (data: unknown) => void): ReturnType<JQueryStatic['ajax']>;
  getJSON(url: string, data?: Record<string, unknown>, callback?: (data: unknown) => void): ReturnType<JQueryStatic['ajax']>;
  each<T>(collection: T[] | Record<string, T>, callback: (index: number | string, value: T) => void): void;
  extend<T extends object>(...objects: T[]): T;
  isArray(obj: unknown): obj is unknown[];
  isFunction(obj: unknown): obj is (...args: unknown[]) => unknown;
  isPlainObject(obj: unknown): obj is Record<string, unknown>;
  isEmptyObject(obj: Record<string, unknown>): boolean;
  isNumeric(value: unknown): boolean;
  trim(str: string): string;
  type(obj: unknown): string;
  noop(): void;
  now(): number;
  parseJSON(json: string): unknown;
  parseHTML(html: string): Node[];
  fn: Record<string, unknown>;
}

const $: JQueryStatic = function(selector: string | HTMLElement | Document | Window | NodeList | HTMLElement[] | (() => void)) {
  if (typeof selector === 'function') {
    if (document.readyState !== 'loading') {
      selector();
    } else {
      document.addEventListener('DOMContentLoaded', selector);
    }
    return createJQueryElement([]);
  }

  if (typeof selector === 'string') {
    if (selector.startsWith('<')) {
      const template = document.createElement('template');
      template.innerHTML = selector.trim();
      return createJQueryElement(Array.from(template.content.children) as HTMLElement[]);
    }
    return createJQueryElement(Array.from(document.querySelectorAll<HTMLElement>(selector)));
  }

  if (selector instanceof Window || selector instanceof Document) {
    return createJQueryElement([document.documentElement]);
  }

  if (selector instanceof NodeList) {
    return createJQueryElement(Array.from(selector) as HTMLElement[]);
  }

  if (Array.isArray(selector)) {
    return createJQueryElement(selector);
  }

  if (selector instanceof HTMLElement) {
    return createJQueryElement([selector]);
  }

  return createJQueryElement([]);
} as JQueryStatic;

// Static methods
$.ajax = (options) => {
  const promise = {
    done: (cb: (data: unknown) => void) => {
      // Mock success
      setTimeout(() => cb({}), 0);
      return promise;
    },
    fail: (cb: (err: unknown) => void) => {
      return promise;
    }
  };
  return promise;
};

$.get = (url, data, callback) => $.ajax({ url, data, method: 'GET', success: callback });
$.post = (url, data, callback) => $.ajax({ url, data, method: 'POST', success: callback });
$.getJSON = (url, data, callback) => $.ajax({ url, data, dataType: 'json', success: callback });

$.each = (collection, callback) => {
  if (Array.isArray(collection)) {
    collection.forEach((val, i) => callback(i, val));
  } else {
    Object.entries(collection).forEach(([key, val]) => callback(key, val));
  }
};

$.extend = (...objects) => Object.assign({}, ...objects);
$.isArray = Array.isArray;
$.isFunction = (obj): obj is (...args: unknown[]) => unknown => typeof obj === 'function';
$.isPlainObject = (obj): obj is Record<string, unknown> => obj !== null && typeof obj === 'object' && Object.getPrototypeOf(obj) === Object.prototype;
$.isEmptyObject = (obj) => Object.keys(obj).length === 0;
$.isNumeric = (value) => !isNaN(parseFloat(value as string)) && isFinite(value as number);
$.trim = (str) => str.trim();
$.type = (obj) => Object.prototype.toString.call(obj).slice(8, -1).toLowerCase();
$.noop = () => {};
$.now = Date.now;
$.parseJSON = JSON.parse;
$.parseHTML = (html) => {
  const template = document.createElement('template');
  template.innerHTML = html.trim();
  return Array.from(template.content.childNodes);
};
$.fn = {};

export default $;
export { $ as jQuery };
