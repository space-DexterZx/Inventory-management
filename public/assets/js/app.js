// Multi-item issue rows
(function () {
  const lines = document.getElementById("item-lines");
  const tpl = document.getElementById("item-line-template");
  const addBtn = document.getElementById("add-item-line");
  if (!lines || !tpl || !addBtn) return;

  function syncRemove() {
    const rows = lines.querySelectorAll(".row");
    rows.forEach((r) => {
      const btn = r.querySelector(".btn-remove");
      if (btn) btn.hidden = rows.length === 1;
    });
  }

  addBtn.addEventListener("click", () => {
    lines.appendChild(tpl.content.cloneNode(true));
    syncRemove();
  });

  lines.addEventListener("click", (e) => {
    if (!e.target.classList.contains("btn-remove")) return;
    e.target.closest(".row").remove();
    syncRemove();
  });

  syncRemove();
})();