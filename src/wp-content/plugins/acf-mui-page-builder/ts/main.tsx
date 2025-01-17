import React from 'react';
import ReactDOM from 'react-dom';
import EditorIframe, { EditorInstance } from 'mui-edit/EditorIframe';
import { Block } from 'mui-edit';

// Define a global variable because imports are not accessible in define callback

window.acf.addAction('prepare_field', (field) => {
  if (field.data.type !== 'pageBuilder') {
    return;
  }
  const el = field.$el[0];
  if (el.classList.contains('mui-page-builder-initialized')) {
    return;
  }
  const scriptEl = el.querySelector('script.settings');
  if (!scriptEl || !scriptEl.textContent) {
    return;
  }
  const settings = JSON.parse(scriptEl.textContent) as {
    editor_url: string,
    jwt_token: string,
  };
  const container = document.createElement('div');
  el.appendChild(container);
  el.classList.add('mui-page-builder-initialized');
  const inputEl = el.querySelector('input[type="hidden"]') as HTMLInputElement;
  let initialData: Block[] = inputEl.value.length > 0 ? JSON.parse(inputEl.value) : [];
  if (!Array.isArray(initialData)) {
    initialData = [];
  }
  let timeoutId = 0;
  let initialized = false;
  ReactDOM.render(
    <EditorIframe
      style={{
        width: '100%',
        height: '80vh',
        border: '1px solid #eee',
      }}
      allow="clipboard-write; clipboard-read; fullscreen;"
      allowfullscreen
      id="wordpress-preview-iframe"
      src={settings.editor_url}
      onLoad={(editor: EditorInstance) => {
        initialized = true;
        editor.dispatch({
          type: 'editor/jwtToken',
          payload: settings.jwt_token
        });
        editor.setData(initialData);
        const event = new CustomEvent('muiEditLoad', {
          detail: {
            editor
          },
        });
        window.muiEdit = editor;
        document.dispatchEvent(event);
      }}
      onChange={(newBlocks) => {
        if (!initialized) {
          return;
        }
        window.clearTimeout(timeoutId);
        timeoutId = window.setTimeout(() => {
          inputEl.value = JSON.stringify(newBlocks);
        }, 200);
      }}
    />,
    container,
  );
});
