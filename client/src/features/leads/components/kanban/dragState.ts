import { ref } from 'vue'

// ponytail: dataTransfer.getData() is flaky across browsers outside the drop
// event; a shared ref sidesteps that entirely and is trivial to reason about.
export const draggedLeadId = ref<string | null>(null)
