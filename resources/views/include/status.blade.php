@if (session('status'))
    <div class="alert alert alert-success alert-dismissible">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"> <span aria-hidden="true"></span> </button>
        <ul class="mb-0">
            <li>
                {!! nl2br(e(session('status'))) !!}
            </li>
        </ul>
    </div>
@endif
