{{-- KYC analytics & reporting. Reviewer speed is shown next to approval/rejection
     mix deliberately — a reviewer with a very low average time and a very high
     approval rate is a quality signal worth a second look, not a leaderboard win. --}}
<div class="grid gap-5 lg:grid-cols-3">
    <x-glass-card solid class="lg:col-span-2">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-strong">Submission volume (14 days)</h3>
        </div>
        @php $maxT = max(1, collect($analytics['trend'])->max('count')); @endphp
        <div class="mt-4 flex h-32 items-end gap-1.5">
            @foreach ($analytics['trend'] as $d)
                <div class="group relative flex-1">
                    <div class="rounded-t bg-brand-500/70" style="height: {{ max(3, ($d['count']/$maxT)*100) }}px" title="{{ \Illuminate\Support\Carbon::parse($d['date'])->format('M j') }}: {{ $d['count'] }}"></div>
                </div>
            @endforeach
        </div>
        <div class="mt-1 flex justify-between text-[10px] text-faint">
            <span>{{ \Illuminate\Support\Carbon::parse($analytics['trend'][0]['date'])->format('M j') }}</span>
            <span>{{ \Illuminate\Support\Carbon::parse(end($analytics['trend'])['date'])->format('M j') }}</span>
        </div>
    </x-glass-card>

    <x-glass-card solid>
        <h3 class="font-semibold text-strong">Documents expiring (30 days)</h3>
        <div class="mt-3 space-y-2">
            @forelse ($analytics['expiring'] as $e)
                <a href="{{ route('admin.kyc.show', $e) }}" class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm hover:surface">
                    <span class="truncate text-body">{{ $e->user->name ?? $e->full_name }}</span>
                    <span class="text-xs text-amber-600">{{ $e->document_expiry_date->diffForHumans() }}</span>
                </a>
            @empty
                <p class="text-sm text-faint">No document expiry dates recorded yet. Reviewers can record the expiry printed on a document from the case workspace.</p>
            @endforelse
        </div>
    </x-glass-card>
</div>

<x-glass-card solid class="mt-5">
    <h3 class="font-semibold text-strong">Reviewer performance (30 days)</h3>
    <p class="mt-1 text-xs text-faint">Speed is shown alongside outcome mix on purpose &mdash; fast approvals with no rejections or escalations at all is a quality flag, not just a productivity win.</p>
    <div class="mt-3 overflow-x-auto">
        <table class="w-full min-w-[500px] text-left text-sm">
            <thead class="border-b border-app text-muted"><tr>
                <th class="py-2 pr-3 font-medium">Reviewer</th>
                <th class="py-2 pr-3 font-medium">Decisions</th>
                <th class="py-2 pr-3 font-medium">Approved</th>
                <th class="py-2 pr-3 font-medium">Rejected</th>
                <th class="py-2 pr-3 font-medium">Avg. time to decision</th>
            </tr></thead>
            <tbody class="divide-y divide-app">
                @forelse ($analytics['reviewers'] as $r)
                    <tr>
                        <td class="py-2 pr-3 text-strong">{{ $r['reviewer'] }}</td>
                        <td class="py-2 pr-3 text-body">{{ $r['decisions'] }}</td>
                        <td class="py-2 pr-3 text-emerald-600">{{ $r['approved'] }}</td>
                        <td class="py-2 pr-3 text-rose-600">{{ $r['rejected'] }}</td>
                        <td class="py-2 pr-3 text-body">{{ $r['avg_hours'] !== null ? $r['avg_hours'].'h' : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-faint">No final decisions recorded in the last 30 days.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-glass-card>

<div class="mt-5 rounded-2xl border border-dashed border-app p-4 text-xs text-faint">
    <strong class="text-body">Reporting note:</strong> CSV export above respects the current filters and always masks document numbers to last 4 digits. PDF/Excel export and a scheduled report delivery are not implemented in this build &mdash; no export library is currently installed in this project.
</div>
