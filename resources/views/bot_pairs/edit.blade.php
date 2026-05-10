@extends('layout.master')

@section('content')

<div class="page-content">

    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-1">Edit Bot Pair</h4>
            <p class="text-muted mb-0">
                {{ $tradingBotPair->symbol }} / {{ $tradingBotPair->entry_timeframe }}
            </p>
        </div>

        <a href="{{ route('bot-pairs.index') }}" class="btn btn-light">
            Back
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <h6 class="card-title">Pair Setting</h6>

            <form method="POST" action="{{ route('bot-pairs.update', $tradingBotPair) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">Symbol</label>
                        <input type="text"
                               name="symbol"
                               class="form-control"
                               value="{{ old('symbol', $tradingBotPair->symbol) }}"
                               required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Entry Timeframe</label>
                        <select name="entry_timeframe" class="form-select" required>
                            @foreach(['M1', 'M5', 'M15', 'M30', 'H1', 'H4', 'D1'] as $tf)
                                <option value="{{ $tf }}" @selected(old('entry_timeframe', $tradingBotPair->entry_timeframe) === $tf)>
                                    {{ $tf }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Higher Timeframes</label>
                        <input type="text"
                               name="higher_timeframes"
                               class="form-control"
                               value="{{ old('higher_timeframes', implode(',', $tradingBotPair->higher_timeframes ?? [])) }}"
                               placeholder="D1,H4,H1">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label d-block">Enabled</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="enabled"
                                   value="1"
                                   @checked(old('enabled', $tradingBotPair->enabled))>
                            <label class="form-check-label">
                                Pair aktif diproses scheduler
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label d-block">Auto Generate</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="auto_generate"
                                   value="1"
                                   @checked(old('auto_generate', $tradingBotPair->auto_generate))>
                            <label class="form-check-label">
                                Generate otomatis saat candle baru
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Notes</label>
                        <input type="text"
                               name="notes"
                               class="form-control"
                               value="{{ old('notes', $tradingBotPair->notes) }}">
                    </div>

                    <div class="col-md-12">
                        <hr>

                        <h6 class="mb-3">Status</h6>

                        <table class="table table-sm">
                            <tr>
                                <th style="width: 260px;">Last Checked</th>
                                <td>{{ $tradingBotPair->last_checked_at ? $tradingBotPair->last_checked_at->format('Y-m-d H:i:s') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Last Generated At</th>
                                <td>{{ $tradingBotPair->last_generated_at ? $tradingBotPair->last_generated_at->format('Y-m-d H:i:s') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Last Generated Candle</th>
                                <td>{{ $tradingBotPair->last_generated_candle_time ? $tradingBotPair->last_generated_candle_time->format('Y-m-d H:i:s') : '-' }}</td>
                            </tr>
                        </table>
                    </div>

                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            Save Changes
                        </button>

                        <a href="{{ route('bot-pairs.index') }}" class="btn btn-light">
                            Cancel
                        </a>
                    </div>

                </div>
            </form>

        </div>
    </div>

</div>

@endsection