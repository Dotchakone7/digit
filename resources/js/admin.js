import { boot } from './livewire';

boot((Alpine) => {
    /** Repeater for key/value product specifications and variants. */
    Alpine.data('repeater', (rows = [], blank = {}) => ({
        rows: rows.length ? rows : [],
        add() {
            this.rows.push({ ...blank, _key: Date.now() + Math.random() });
        },
        remove(index) {
            this.rows.splice(index, 1);
        },
    }));

    /** Slug preview generated from the name field (the server re-validates it). */
    Alpine.data('slugger', (name = '', slug = '', locked = false) => ({
        name,
        slug,
        locked,
        sync() {
            if (this.locked) return;
            this.slug = this.name
                .normalize('NFD')
                .replace(/[̀-ͯ]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        },
    }));

    /** Local previews of the images picked for upload (object URLs, nothing leaves the browser). */
    Alpine.data('imagePicker', (maxBytes) => ({
        previews: [],
        dragging: false,
        drop(event) {
            this.dragging = false;
            const transfer = new DataTransfer();
            [...this.$refs.input.files, ...event.dataTransfer.files]
                .filter((file) => /^image\/(jpeg|png|webp)$/.test(file.type))
                .forEach((file) => transfer.items.add(file));
            this.$refs.input.files = transfer.files;
            this.sync();
        },
        remove(index) {
            const transfer = new DataTransfer();
            [...this.$refs.input.files].forEach((file, i) => i !== index && transfer.items.add(file));
            this.$refs.input.files = transfer.files;
            this.sync();
        },
        sync() {
            this.previews.forEach((preview) => URL.revokeObjectURL(preview.url));
            this.previews = [...this.$refs.input.files].map((file) => ({
                name: file.name,
                url: URL.createObjectURL(file),
                tooBig: file.size > maxBytes,
            }));
        },
        destroy() {
            this.previews.forEach((preview) => URL.revokeObjectURL(preview.url));
        },
    }));
});
