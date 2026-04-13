@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white'])

@php
$popoverId = 'dd-' . uniqid();
$anchorName = '--' . $popoverId;

$width = match ($width) {
    '48' => 'w-48',
    default => $width,
};

$alignClass = match ($align) {
    'left'  => 'dd-popover-align-left',
    'top'   => 'dd-popover-align-top',
    default => 'dd-popover-align-right',
};
@endphp

<div
    class="relative flex"
    x-data="{
        isOpen: false,
        align: '{{ $align }}',

        getPanel() {
            return document.getElementById('{{ $popoverId }}');
        },

        anchorSupported() {
            return CSS.supports('position-anchor', '--x');
        },

        positionPanel() {
            if (this.anchorSupported()) { return; }
            const panel = this.getPanel();
            const trigger = this.$el.querySelector('[data-dd-trigger]');
            if (! panel || ! trigger) { return; }
            const rect = trigger.getBoundingClientRect();
            panel.style.position = 'fixed';
            panel.style.top = (rect.bottom + 8) + 'px';
            panel.style.width = '';
            if (this.align === 'left') {
                panel.style.left = rect.left + 'px';
                panel.style.right = 'auto';
            } else {
                panel.style.right = (window.innerWidth - rect.right) + 'px';
                panel.style.left = 'auto';
            }
        },

        onToggle(event) {
            this.isOpen = event.newState === 'open';
            if (this.isOpen) {
                this.positionPanel();
                window.addEventListener('scroll', this._onScroll = () => this.positionPanel(), { passive: true, capture: true });
                window.addEventListener('resize', this._onResize = () => this.positionPanel(), { passive: true });
            } else {
                window.removeEventListener('scroll', this._onScroll, { capture: true });
                window.removeEventListener('resize', this._onResize);
            }
        },

        init() {
            const panel = this.getPanel();
            if (panel) {
                panel.addEventListener('toggle', (e) => this.onToggle(e));
            }
        },
    }"
    x-init="init()"
>
    {{-- Trigger wrapper: the slot contains the real <button> for keyboard/a11y --}}
    <div
        data-dd-trigger
        role="none"
        style="anchor-name: {{ $anchorName }}"
        class="flex items-center justify-center"
        @click="getPanel()?.togglePopover()"
    >
        {{ $trigger }}
    </div>

    {{--
        The popover element lives in the top layer so it is never clipped by
        overflow-hidden ancestors (e.g. the glass navbar shell).
        x-show on the inner content div drives the Alpine transition; the outer
        popover element is always "shown" once promoted to the top layer.
    --}}
    <div
        id="{{ $popoverId }}"
        popover="auto"
        style="position-anchor: {{ $anchorName }}"
        class="dd-popover {{ $alignClass }} {{ $width }}"
    >
        <div
            x-show="isOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            style="display:none"
        >
            <div class="rounded-md shadow-lg ring-1 ring-black ring-opacity-5 {{ $contentClasses }}">
                {{ $content }}
            </div>
        </div>
    </div>
</div>
