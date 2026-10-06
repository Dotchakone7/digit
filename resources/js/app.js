import { boot } from './livewire';
import { registerComponents, registerCartPage } from './components';

boot((Alpine) => {
    registerComponents(Alpine);
    registerCartPage(Alpine);
});
