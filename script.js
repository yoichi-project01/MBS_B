function handleClick(storeName) {
  alert(`${storeName} がクリックされました`);

  // localStorageに上書き保存（キー：selectedStore）
  localStorage.setItem("selectedStore", storeName);
}
