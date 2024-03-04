<div id="flash-overlay-modal" class="modal fade {{ $modalClass ?? '' }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">{{ $title }}</h4>
    
                <button type="button" class="close" data-bs-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">{{ t('Close') }}</span>
                </button>
            </div>
            
            <div class="modal-body">
                <p>{!! $body !!}</p>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">{{ t('Close') }}</button>
            </div>
        </div>
    </div>
</div>
