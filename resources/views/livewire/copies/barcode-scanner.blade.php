<section
    class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm"
    x-data="{
        scanning: false,
        stream: null,
        timer: null,
        cameraError: '',
        async startCamera() {
            this.cameraError = '';

            if (!('BarcodeDetector' in window)) {
                this.cameraError = 'Dieser Browser unterstützt die Kamera-Barcodesuche nicht.';
                return;
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                this.cameraError = 'Der Kamerazugriff ist in diesem Browser nicht verfügbar.';
                return;
            }

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: { ideal: 'environment' }
                    },
                    audio: false
                });

                this.$refs.video.srcObject = this.stream;
                await this.$refs.video.play();
                this.scanning = true;

                const detector = new BarcodeDetector({
                    formats: [
                        'ean_13',
                        'ean_8',
                        'code_128',
                        'code_39',
                        'upc_a',
                        'upc_e',
                        'itf'
                    ]
                });

                this.timer = window.setInterval(async () => {
                    if (!this.scanning || this.$refs.video.readyState < 2) {
                        return;
                    }

                    try {
                        const results = await detector.detect(this.$refs.video);

                        if (results.length > 0) {
                            const value = results[0].rawValue;
                            this.stopCamera();
                            await $wire.capture(value);
                        }
                    } catch (error) {
                        this.cameraError = 'Der Barcode konnte nicht gelesen werden.';
                    }
                }, 400);
            } catch (error) {
                this.cameraError = 'Der Kamerazugriff wurde abgelehnt oder ist nicht möglich.';
                this.stopCamera();
            }
        },
        stopCamera() {
            this.scanning = false;

            if (this.timer) {
                window.clearInterval(this.timer);
                this.timer = null;
            }

            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }

            if (this.$refs.video) {
                this.$refs.video.srcObject = null;
            }
        }
    }"
>
    <h2 class="text-xl font-semibold">Barcode erfassen</h2>
    <p class="mt-2 text-sm text-stone-600">
        Kamera verwenden, mit einem USB-/Bluetooth-Scanner in das Feld scannen oder den Code manuell eingeben.
    </p>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <form wire:submit="save" class="space-y-3">
            <label class="block">
                <span class="text-sm font-medium">Barcode</span>
                <input
                    wire:model="barcode"
                    wire:blur="formatBarcode"
                    type="text"
                    inputmode="text"
                    autocomplete="off"
                    autofocus
                    class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2 font-mono"
                >
                @error('barcode') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white">
                Barcode speichern
            </button>
        </form>

        <div>
            <div class="overflow-hidden rounded-xl bg-black">
                <video x-ref="video" class="aspect-video w-full object-cover" muted playsinline></video>
            </div>

            <div class="mt-3 flex flex-wrap gap-3">
                <button
                    type="button"
                    x-on:click="startCamera"
                    x-show="!scanning"
                    class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium"
                >
                    Kamera starten
                </button>

                <button
                    type="button"
                    x-on:click="stopCamera"
                    x-show="scanning"
                    class="rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium"
                >
                    Kamera stoppen
                </button>
            </div>

            <p x-show="cameraError !== ''" x-text="cameraError" class="mt-3 text-sm text-red-700"></p>
        </div>
    </div>
</section>
