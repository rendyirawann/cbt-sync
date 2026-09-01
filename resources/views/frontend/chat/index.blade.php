@extends(auth()->user()->hasRole('Siswa') ? 'frontend.layout.app' : 'backend.layout.app')
@section('title', 'Pesan Internal')

@section('content')

<style>
.contact-item { cursor: pointer; border-radius: 10px; transition: all 0.2s ease; }
.contact-item:hover { background: #f5f8fa; }
.contact-item.active { background: #f0f4ff; border-left: 3px solid #6a11cb; }
.chat-bubble { max-width: 75%; word-break: break-word; }
.chat-bubble-mine { background: linear-gradient(135deg,#6a11cb,#2575fc); color: #fff; border-radius: 18px 18px 4px 18px; }
.chat-bubble-other { background: #f5f8fa; color: #181c32; border-radius: 18px 18px 18px 4px; }
.chat-scroll { height: calc(100vh - 380px); min-height: 300px; overflow-y: auto; }
.chat-input-area { border-top: 1px solid #eff2f5; }
#typing-area { resize: none; }
.online-dot { width: 10px; height: 10px; background: #50cd89; border-radius: 50%; display: inline-block; }
.unread-badge { font-size: 10px; min-width: 18px; height: 18px; border-radius: 9px; }
</style>

<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Pesan Internal</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Portal</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Chat</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        <div class="d-flex flex-column flex-lg-row gap-5">

            {{-- ======== SIDEBAR KONTAK ======== --}}
            <div class="flex-column flex-lg-row-auto w-100 w-lg-300px">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header border-0 pt-6 pb-3">
                        <div class="card-title w-100 flex-column">
                            <h3 class="fw-bolder text-dark mb-3">Percakapan</h3>
                            <div class="position-relative w-100">
                                <i class="ki-outline ki-magnifier fs-4 text-muted position-absolute" style="top:50%;left:12px;transform:translateY(-50%);"></i>
                                <input type="text" id="search-contact" class="form-control form-control-solid ps-10" placeholder="Cari kontak...">
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-3 pb-0 pe-3 ps-3">
                        <div style="max-height: calc(100vh - 320px); overflow-y:auto;">
                            @forelse($allContacts as $contact)
                            <div class="contact-item d-flex align-items-center p-3 mb-1"
                                 data-id="{{ $contact->id }}"
                                 data-name="{{ $contact->name }}"
                                 data-avatar="{{ $contact->avatar_url }}"
                                 data-role="{{ $contact->hasRole('Guru') ? 'Guru' : 'Siswa' }}">
                                <div class="symbol symbol-45px symbol-circle me-3 flex-shrink-0">
                                    <img src="{{ $contact->avatar_url }}" alt="{{ $contact->name }}">
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-dark fw-bold fs-7 text-truncate">{{ $contact->name }}</span>
                                    </div>
                                    <span class="text-muted fs-8">{{ $contact->hasRole('Guru') ? 'Guru Pengajar' : 'Siswa' }}</span>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-10">
                                <i class="ki-outline ki-people fs-4x text-gray-200 mb-3"></i>
                                <p class="text-muted fs-7">Belum ada kontak tersedia.</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======== AREA CHAT ======== --}}
            <div class="flex-lg-row-fluid">
                <div class="card shadow-sm border-0" id="chat-messenger" style="min-height: 500px;">

                    {{-- Header --}}
                    <div class="card-header border-0 py-4" id="chat-header" style="display:none!important;">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-45px symbol-circle me-3">
                                <img id="chat-receiver-avatar" src="" alt="">
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fs-5 fw-bolder text-gray-900" id="chat-receiver-name">-</span>
                                    <span class="badge badge-light-success fs-9" id="chat-receiver-role">-</span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="online-dot"></span>
                                    <span class="text-muted fs-8">Online</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Empty State --}}
                    <div id="chat-empty-state" class="d-flex flex-column flex-center" style="height:500px;">
                        <i class="ki-outline ki-messages fs-5x text-gray-200 mb-5"></i>
                        <h4 class="text-gray-500 fw-semibold">Pilih kontak untuk memulai percakapan</h4>
                        <p class="text-muted fs-7">Kamu bisa mengirim pesan ke guru pengajarmu di sini</p>
                    </div>

                    {{-- Messages --}}
                    <div class="card-body pt-4 pb-0 d-none" id="chat-body">
                        <div class="chat-scroll pe-3" id="chat-messages">
                            {{-- Messages loaded dynamically --}}
                        </div>
                    </div>

                    {{-- Loading --}}
                    <div id="chat-loading" class="d-none d-flex flex-center" style="height:400px;">
                        <span class="spinner-border text-primary" style="width:3rem;height:3rem;"></span>
                    </div>

                    {{-- Input --}}
                    <div class="card-footer chat-input-area pt-4 d-none" id="chat-footer">
                        <form id="chat-send-form">
                            <div class="d-flex align-items-end gap-3">
                                <div class="flex-grow-1">
                                    <textarea id="typing-area" name="message"
                                        class="form-control form-control-solid"
                                        rows="2"
                                        placeholder="Tulis pesan..."
                                        style="border-radius:12px;"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary fw-bold px-5" id="btn-send" style="border-radius:12px;">
                                    <i class="ki-outline ki-send fs-3"></i>
                                </button>
                            </div>
                            <div class="text-muted fs-8 mt-2">
                                <kbd>Enter</kbd> untuk baris baru &bull; Klik tombol untuk kirim
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
    const ME_ID = "{{ auth()->id() }}";
    const ME_AVATAR = "{{ auth()->user()->avatar_url }}";
    let currentReceiverId = null;
    let pollingInterval = null;

    // ===== SELECT CONTACT =====
    $(document).on('click', '.contact-item', function() {
        const id     = $(this).data('id');
        const name   = $(this).data('name');
        const avatar = $(this).data('avatar');
        const role   = $(this).data('role');

        if (id === currentReceiverId) return;
        currentReceiverId = id;

        $('.contact-item').removeClass('active');
        $(this).addClass('active');

        // Update header
        $('#chat-receiver-name').text(name);
        $('#chat-receiver-avatar').attr('src', avatar);
        $('#chat-receiver-role').text(role);
        $('#chat-header').css('display', 'flex');
        $('#chat-empty-state').addClass('d-none');
        $('#chat-body').removeClass('d-none');
        $('#chat-footer').removeClass('d-none');
        $('#chat-loading').addClass('d-none');

        loadMessages(id);

        // Poll every 5 seconds
        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = setInterval(() => loadMessages(id, true), 5000);
    });

    // ===== LOAD MESSAGES =====
    function loadMessages(receiverId, silent = false) {
        if (!silent) {
            $('#chat-messages').empty();
            $('#chat-loading').removeClass('d-none');
            $('#chat-body').addClass('d-none');
        }

        $.get(`/portal/chat/${receiverId}`, function(res) {
            if (!silent) {
                $('#chat-loading').addClass('d-none');
                $('#chat-body').removeClass('d-none');
            }

            const container = $('#chat-messages');
            const prevScrollTop = container.scrollTop();
            const prevScrollHeight = container[0].scrollHeight;

            container.empty();

            if (res.messages.length === 0) {
                container.html(`
                    <div class="text-center py-10 text-muted">
                        <i class="ki-outline ki-message-question fs-4x text-gray-200 mb-3"></i>
                        <p class="fw-semibold">Belum ada pesan. Mulailah percakapan!</p>
                    </div>`);
            }

            let lastDate = null;
            res.messages.forEach(msg => {
                const isMine = msg.sender_id === ME_ID;
                const msgDate = new Date(msg.created_at);
                const dateStr = msgDate.toLocaleDateString('id-ID', {weekday:'long', day:'numeric', month:'long', year:'numeric'});
                const timeStr = msgDate.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});

                // Date separator
                if (dateStr !== lastDate) {
                    lastDate = dateStr;
                    container.append(`
                        <div class="d-flex align-items-center my-5">
                            <div class="flex-grow-1 bg-gray-200" style="height:1px;"></div>
                            <span class="badge badge-light-secondary mx-4 fw-semibold">${dateStr}</span>
                            <div class="flex-grow-1 bg-gray-200" style="height:1px;"></div>
                        </div>`);
                }

                const avatarSrc = isMine ? ME_AVATAR : res.receiver.avatar_url;
                container.append(`
                    <div class="d-flex justify-content-${isMine ? 'end' : 'start'} mb-4">
                        ${!isMine ? `<div class="symbol symbol-35px symbol-circle me-3 align-self-end flex-shrink-0"><img src="${avatarSrc}" alt=""></div>` : ''}
                        <div>
                            <div class="chat-bubble ${isMine ? 'chat-bubble-mine' : 'chat-bubble-other'} px-5 py-3 mb-1">
                                ${escapeHtml(msg.message)}
                            </div>
                            <div class="text-muted fs-8 ${isMine ? 'text-end' : 'text-start'}">${timeStr}</div>
                        </div>
                        ${isMine ? `<div class="symbol symbol-35px symbol-circle ms-3 align-self-end flex-shrink-0"><img src="${ME_AVATAR}" alt=""></div>` : ''}
                    </div>`);
            });

            // Auto-scroll to bottom (always on first load, on poll only if near bottom)
            if (!silent) {
                container.scrollTop(container[0].scrollHeight);
            } else {
                const distFromBottom = prevScrollHeight - prevScrollTop - container.height();
                if (distFromBottom < 100) {
                    container.scrollTop(container[0].scrollHeight);
                }
            }
        }).fail(() => {
            if (!silent) {
                $('#chat-loading').addClass('d-none');
                $('#chat-body').removeClass('d-none');
                $('#chat-messages').html('<div class="text-center text-danger py-10">Gagal memuat pesan.</div>');
            }
        });
    }

    // ===== SEND MESSAGE =====
    $('#chat-send-form').on('submit', function(e) {
        e.preventDefault();
        const message = $('#typing-area').val().trim();
        if (!message || !currentReceiverId) return;

        const btn = $('#btn-send');
        btn.prop('disabled', true);

        $.post("{{ route('student.chat.store') }}", {
            _token: "{{ csrf_token() }}",
            receiver_id: currentReceiverId,
            message: message
        }).done(function() {
            $('#typing-area').val('');
            loadMessages(currentReceiverId, true);
            // Force scroll to bottom immediately
            setTimeout(() => {
                const c = $('#chat-messages');
                c.scrollTop(c[0].scrollHeight);
            }, 100);
        }).fail(function() {
            Swal.fire('Gagal!', 'Pesan tidak terkirim. Coba lagi.', 'error');
        }).always(function() {
            btn.prop('disabled', false);
            $('#typing-area').focus();
        });
    });

    // ===== SEARCH CONTACT =====
    $('#search-contact').on('input', function() {
        const q = $(this).val().toLowerCase();
        $('.contact-item').each(function() {
            const name = $(this).data('name').toLowerCase();
            $(this).toggle(name.includes(q));
        });
    });

    // ===== HELPER =====
    function escapeHtml(text) {
        return text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
    }

    // Cleanup on leave
    window.addEventListener('beforeunload', () => {
        if (pollingInterval) clearInterval(pollingInterval);
    });
</script>
@endpush
@endsection
