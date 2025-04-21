<div class="p-4">
    <h3 class="text-lg font-bold mb-4">Старые данные</h3>
    <pre class="bg-gray-100 p-3 rounded text-sm overflow-auto">
        <code class="language-json">{{ json_encode(json_decode($oldData, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code>
    </pre>

    <h3 class="text-lg font-bold mt-6 mb-4">Новые данные</h3>
    <pre class="bg-gray-100 p-3 rounded text-sm overflow-auto">
        <code class="language-json">{{ json_encode(json_decode($newData, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code>
    </pre>
</div>
