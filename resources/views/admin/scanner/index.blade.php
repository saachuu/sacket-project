@extends('layouts.app')

@section('title', 'Global Ticket Scanner')

@section('content')
    <div class="max-w-xl mx-auto">
        <h1 class="text-3xl font-bold mb-6 text-gray-900 text-center">Global Event Scanner</h1>

        {{-- INFO: Tidak perlu dropdown event lagi --}}

        <div class="bg-white overflow-hidden shadow-lg rounded-lg relative">
            {{-- Loading Indicator --}}
            <div id="loading-scan"
                class="hidden absolute inset-0 bg-white bg-opacity-80 z-10 flex items-center justify-center">
                <div class="text-center">
                    <svg class="animate-spin h-10 w-10 text-blue-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <p class="mt-2 font-semibold text-gray-600">Verifikasi Tiket...</p>
                </div>
            </div>

            <div class="p-4">
                {{-- Area Kamera --}}
                <div id="reader" class="w-full rounded-lg overflow-hidden bg-black shadow-inner"
                    style="min-height: 300px;"></div>

                {{-- Kontrol Kamera --}}
                <div class="mt-4 flex justify-center gap-2" id="camera-controls">
                    <button id="start-camera"
                        class="bg-blue-600 text-white px-6 py-2 rounded-full font-bold shadow hover:bg-blue-700 transition">
                        MULAI SCAN
                    </button>
                    <button id="stop-camera"
                        class="hidden bg-gray-500 text-white px-6 py-2 rounded-full font-bold shadow hover:bg-gray-600 transition">
                        STOP
                    </button>
                </div>

                {{-- Area Hasil Scan --}}
                <div id="result" class="mt-6 p-6 rounded-xl text-center hidden border-2 transition-all duration-300">
                    <div class="text-6xl mb-4" id="result-icon"></div>
                    <h2 id="result-message" class="font-bold text-2xl uppercase tracking-wide"></h2>

                    {{-- Kotak Detail Tiket --}}
                    <div id="ticket-details" class="mt-4 text-left bg-white bg-opacity-50 p-4 rounded-lg"></div>

                    {{-- Tombol Scan Next --}}
                    <button id="reset-scan"
                        class="mt-4 w-full bg-gray-800 text-white py-2 rounded shadow hover:bg-gray-700 hidden">
                        Scan Berikutnya
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Library QR Code --}}
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const html5QrCode = new Html5Qrcode("reader");
            const startBtn = document.getElementById('start-camera');
            const stopBtn = document.getElementById('stop-camera');
            const resetBtn = document.getElementById('reset-scan');

            const resultContainer = document.getElementById('result');
            const resultMessage = document.getElementById('result-message');
            const resultIcon = document.getElementById('result-icon');
            const ticketDetails = document.getElementById('ticket-details');
            const loadingScan = document.getElementById('loading-scan');

            let isScanning = false;
            let isProcessing = false;

            const config = {
                fps: 10,
                qrbox: {
                    width: 250,
                    height: 250
                },
                aspectRatio: 1.0
            };

            const beepSuccess = new Audio('https://cdn.pixabay.com/audio/2022/03/15/audio_2b436202d0.mp3');
            const beepFail = new Audio('https://cdn.pixabay.com/audio/2021/08/04/audio_0625c1539c.mp3');

            // 1. FUNGSI MULAI KAMERA
            startBtn.addEventListener('click', () => {
                // Hapus validasi dropdown event di sini
                html5QrCode.start({
                        facingMode: "environment"
                    }, config, onScanSuccess)
                    .then(() => {
                        isScanning = true;
                        startBtn.classList.add('hidden');
                        stopBtn.classList.remove('hidden');
                        resultContainer.classList.add('hidden');
                        resetBtn.classList.add('hidden');
                    })
                    .catch(err => {
                        console.error("Gagal menyalakan kamera", err);
                        alert("Gagal akses kamera. Pastikan izin browser diberikan.");
                    });
            });

            // 2. FUNGSI STOP KAMERA
            stopBtn.addEventListener('click', () => {
                stopScanner();
            });

            function stopScanner() {
                html5QrCode.stop().then(() => {
                    isScanning = false;
                    startBtn.classList.remove('hidden');
                    stopBtn.classList.add('hidden');
                }).catch(err => console.error(err));
            }

            // 3. SAAT QR TERDETEKSI
            function onScanSuccess(decodedText, decodedResult) {
                if (isProcessing) return;

                isProcessing = true;
                loadingScan.classList.remove('hidden');
                html5QrCode.pause(); // Pause kamera saat loading

                verifyTicket(decodedText);
            }

            // 4. KIRIM KE BACKEND
            async function verifyTicket(uniqueCode) {
                try {
                    const response = await fetch(
                    "{{ route('ticket.verify') }}", { // Pakai helper route blade agar aman
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            unique_code: uniqueCode
                            // Hapus event_id dari sini
                        })
                    });
                    const data = await response.json();

                    // Mainkan suara
                    if (data.status === 'success') beepSuccess.play();
                    else beepFail.play();

                    displayResult(data.status, data.message, data);

                } catch (error) {
                    console.error(error);
                    displayResult('error', 'Error Sistem', {
                        detail: 'Gagal menghubungi server.'
                    });
                    beepFail.play();
                } finally {
                    isProcessing = false;
                    loadingScan.classList.add('hidden');
                    // Kamera tetap dipause sampai tombol "Scan Berikutnya" ditekan atau otomatis resume (opsional)
                }
            }

            // Tombol Reset untuk scan lagi
            resetBtn.addEventListener('click', () => {
                resultContainer.classList.add('hidden');
                html5QrCode.resume();
            });

            // 5. TAMPILKAN HASIL
            function displayResult(status, message, data = null) {
                resultContainer.classList.remove('hidden', 'border-green-500', 'bg-green-100', 'border-red-500',
                    'bg-red-100', 'border-yellow-500', 'bg-yellow-100');
                resultContainer.classList.add('block');
                resetBtn.classList.remove('hidden');

                if (status === 'success') {
                    // --- SUKSES ---
                    resultContainer.classList.add('border-green-500', 'bg-green-100', 'text-green-900');
                    resultIcon.innerHTML = '✅';

                    ticketDetails.innerHTML = `
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Event</p>
                            <p class="text-xl font-bold text-blue-700">${data.data.event_name}</p>

                            <div class="grid grid-cols-2 gap-4 mt-2">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Pengunjung</p>
                                    <p class="font-medium">${data.data.owner}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Kategori</p>
                                    <p class="font-medium">${data.data.ticket_category}</p>
                                </div>
                            </div>
                            <div class="mt-2 pt-2 border-t border-green-200 text-center">
                                <p class="text-xs text-gray-500">Waktu Scan</p>
                                <p class="font-mono text-lg font-bold">${data.checked_in_at}</p>
                            </div>
                        </div>
                    `;
                } else {
                    // --- GAGAL ---
                    resultContainer.classList.add('border-red-500', 'bg-red-100', 'text-red-900');
                    resultIcon.innerHTML = '⛔';

                    let detailHtml = '';
                    if (data && data.detail) {
                        detailHtml = `<p class="font-semibold text-red-700 mb-2">${data.detail}</p>`;
                    }

                    if (data && data.data) {
                        // Data parsial jika tiket ketemu tapi invalid (misal sudah dipakai/beda event)
                        detailHtml += `
                            <div class="space-y-1 text-sm opacity-80 border-t border-red-200 pt-2">
                                <p><strong>Event:</strong> ${data.data.event_name || '-'}</p>
                                <p><strong>Pemilik:</strong> ${data.data.owner || '-'}</p>
                                ${data.checked_in_at ? `<p class="text-red-700 font-bold bg-red-200 inline-block px-2 rounded">Digunakan: ${data.checked_in_at}</p>` : ''}
                            </div>
                        `;
                    }

                    ticketDetails.innerHTML = detailHtml;
                }

                resultMessage.textContent = message;
            }
        });
    </script>
@endpush
