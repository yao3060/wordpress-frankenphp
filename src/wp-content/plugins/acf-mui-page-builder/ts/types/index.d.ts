import JQuery from 'jquery';
import {EditorInstance} from "mui-edit/EditorIframe";
declare global {

    interface AcfField {
        $el: JQuery<HTMLElement>,
        cid: string,
        data: {
            key: string,
            name: string,
            type: string,
        },
    }
    interface Acf {
        addAction(action: 'prepare' | 'ready', callback: () => void);
        addAction(action: 'new_field' | 'prepare_field' | 'ready_field' | 'load_file' | 'append_field', callback: (field: AcfField) => void);
    }

    interface Window {
        $: JQuery,
        acf: Acf;
        muiEdit?: EditorInstance,
    }
}

export {}