export const tailwindTheme = () => {
    return {
        framework: 'tailwind',
        table: 'table table-sm mt-5',
        thead: 'bg-gray-800 text-white',
        th: 'text-slate-700',
        td: '',
        tr: '',
        trEven: '',
        trOdd: '',
        row: 'grid-rows-1',
        column: 'flex',
        label: 'label',
        input: 'p-1 border',
        select: 'p-1 border',
        field: 'flex-initial ml-2',
        inline: 'is-horizontal',
        right: 'text-right',
        left: 'text-left',
        center: 'text-center mt-2',
        contentCenter: 'justify-center text-center',
        icon: 'icon',
        small: 'is-small',
        nomargin: 'marginless',
        button: 'button',
        groupTr: 'is-selected',
        dropdown: {
            container: 'dropdown flex-initial m-2 relative',
            trigger: 'dropdown-trigger border round p-1',
            menu: 'dropdown-menu absolute z-50 bg-white border p-2',
            content: 'dropdown-content truncate flex-1',
            item: 'dropdown-item mb-1 flex',
            caret: 'fa fa-angle-down',
            checkbox: 'mt-1',
            text: 'text-left ml-1'
        },
        pagination: {
            nav: 'text-center pt-4',
            count: 'text-center flex-row',
            wrapper: 'pagination',
            list: 'flex flex-row',
            item: 'mx-0.5 transition duration-200 border shadow-sm inline-flex items-center justify-center py-1 px-2 rounded-md font-medium cursor-pointer focus:ring-4 focus:ring-primary focus:ring-opacity-20',
            link: '',
            next: '',
            prev: '',
            active: 'bg-blue-900 border-blue-900 text-white',
            disabled: 'bg-white'
        }
    }
};

export default tailwindTheme;
