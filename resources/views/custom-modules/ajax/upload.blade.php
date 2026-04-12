<x-cards.data class="w-100">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>@lang('app.install')/@lang('app.update') @lang('app.module')</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('custom-modules.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="module_file">@lang('app.module') @lang('app.file')</label>
                            <input type="file" name="module_file" id="module_file" class="form-control" accept=".zip">
                        </div>
                        <button type="submit" class="btn btn-primary">@lang('app.upload')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-cards.data>
