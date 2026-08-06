import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import ComposerBody from 'flarum/forum/components/ComposerBody';
import DiscussionComposer from 'flarum/forum/components/DiscussionComposer';
import ReplyComposer from 'flarum/forum/components/ReplyComposer';
import EditPostComposer from 'flarum/forum/components/EditPostComposer';

interface CaptureState {
  t0: number;
  token: string | null;
}

let capture: CaptureState = { t0: 0, token: null };
const HONEYPOT_NAME = 'ms_website';

function armCapture() {
  capture = { t0: Date.now(), token: null };
  app
    .request<{ token?: string }>({
      method: 'GET',
      url: app.forum.attribute<string>('apiUrl') + '/modshield/capture-token',
    })
    .then((res) => {
      capture.token = res && typeof res.token === 'string' ? res.token : null;
    })
    .catch(() => {
      capture.token = null; // missing token is itself a signal; never block the composer
    });
}

function injectHoneypot(element: Element) {
  if (element.querySelector(`input[name="${HONEYPOT_NAME}"]`)) return;
  const input = document.createElement('input');
  input.type = 'text';
  input.name = HONEYPOT_NAME;
  input.autocomplete = 'url';
  input.tabIndex = -1;
  input.setAttribute('aria-hidden', 'true');
  // Off-screen, not display:none — naive autofill bots still fill it.
  input.style.cssText = 'position:absolute;left:-9999px;top:-9999px;height:1px;width:1px;opacity:0;';
  (element.querySelector('form') ?? element).appendChild(input);
}

function captureAttributes(element: Element | null): Record<string, unknown> {
  const hp = element?.querySelector<HTMLInputElement>(`input[name="${HONEYPOT_NAME}"]`);
  return {
    modshieldToken: capture.token,
    modshieldFillMs: capture.t0 > 0 ? Date.now() - capture.t0 : null,
    modshieldHoneypot: hp ? hp.value : '',
  };
}

app.initializers.add('modshield-flarum', () => {
  extend(ComposerBody.prototype, 'oncreate', function (this: ComposerBody) {
    armCapture();
    injectHoneypot(this.element);
  });

  for (const composer of [DiscussionComposer, ReplyComposer, EditPostComposer]) {
    extend(composer.prototype, 'data', function (this: any, data: Record<string, unknown>) {
      Object.assign(data, captureAttributes(this.element ?? document.querySelector('.ComposerBody')));
    });
  }
});
