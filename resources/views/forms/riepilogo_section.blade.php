<div class="fi-section rounded-xl border border-gray-300 bg-white shadow-sm overflow-hidden" style="border: 1px solid #d1d5db;">
    <table class="w-full text-left table-fixed" style="border-collapse: collapse; width: 100%;">
        <thead>
            <tr style="background-color: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                <th style="padding: 12px; text-align: left; font-size: 14px; font-weight: bold; color: #374151; border-right: 1px solid #e5e7eb; width: 30%;">Campo</th>
                <th style="padding: 12px; text-align: left; font-size: 14px; font-weight: bold; color: #374151;">Valore</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($fields as $label => $value)
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    {{-- Colonna Label --}}
                    <td style="padding: 10px 15px; font-size: 13px; font-weight: 600; color: #4b5563; background-color: #f9fafb; border-right: 1px solid #e5e7eb;">
                        {{ $label }}
                    </td>
                    {{-- Colonna Valore --}}
                    <td style="padding: 10px 15px; font-size: 13px; color: #111827; background-color: #ffffff;">
                        @php
                            $displayValue = $value;
                        @endphp

                        @if(blank($displayValue) || $displayValue === '-')
                            <span style="color: #9ca3af; font-style: italic;">Non inserito</span>
                        @else
                            <div style="line-height: 1.5;">
                                {!! $displayValue !!}
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>