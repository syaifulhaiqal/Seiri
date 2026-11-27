/**
 * A sidebar section that folds its links away. The section holding the current page is always open;
 * the others remember whether they were left open or closed.
 *
 * <li x-data="navGroup('hr', isCurrentGroup)"> <button x-on:click="toggle()"> <ul x-show="open">
 */
export default (key, isCurrent = false) => {
    const storageKey = `nav-group:${key}`;

    return {
        open: isCurrent || localStorage.getItem(storageKey) === '1',

        toggle() {
            this.open = ! this.open;
            localStorage.setItem(storageKey, this.open ? '1' : '0');
        },
    };
};
