import Alpine from 'alpinejs';
import 'friendly-challenge/widget';
import '@fontsource/atkinson-hyperlegible/400.css';
import '@fontsource/atkinson-hyperlegible/700.css';
import '@fontsource-variable/jetbrains-mono';

window.Alpine = Alpine;

// Alpine components register themselves here (resources/js/components/*.js).
const components = import.meta.glob('./components/*.js', { eager: true });
Object.values(components).forEach((module) => module.default?.(Alpine));

Alpine.start();
