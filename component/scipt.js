// script.js
window.addEventListener('DOMContentLoaded', () => {
    fetch('../component/header.html')
      .then(response => response.text())
      .then(data => {
        document.getElementById('header').innerHTML = data;
      });
  });
  