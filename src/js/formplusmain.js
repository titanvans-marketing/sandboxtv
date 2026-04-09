
/* ========== Helper: DOM Ready ========== */
function onReady(fn) {
  if (document.readyState !== 'loading') fn();
  else document.addEventListener('DOMContentLoaded', fn);
}

/* ========== 5s Min Submission Time + reCAPTCHA v3 ========== */
onReady(function () {
  var form = document.getElementById('form1');

  var tsField = document.getElementById('form_start_ts');

  var recField = document.getElementById('recaptchaToken');

  if (tsField) tsField.value = String(Date.now());

  if (!form) return;

  form.addEventListener('submit', function (e) {
    // Enforce 5s min
    var start = parseInt(tsField && tsField.value ? tsField.value : '0', 10);

    var elapsed = Date.now() - start;

    if (!start || elapsed < 5000) {
      e.preventDefault();
      alert('Please take a moment to complete the form. You can submit after 5 seconds.');
      return;
    }

    // Refresh reCAPTCHA token just before final submit
    e.preventDefault();

    if (typeof grecaptcha !== 'undefined' && grecaptcha.ready) {
      grecaptcha.ready(function () {
        grecaptcha.execute('6LeXTvIqAAAAAFEs2ICE9rHgjir0B0IdtcqL74xP', { action: 'submit' })
          .then(function (token) {
            if (recField) recField.value = token;
            form.submit();
          })
          .catch(function () {
            // If reCAPTCHA fails, still block to avoid empty token
            alert('Could not verify reCAPTCHA. Please try again.');
          });
      });
    } else {
      alert('reCAPTCHA not loaded. Please reload the page and try again.');
    }
  });
});

/* ========== Phone number input formatting ========== */
onReady(function () {
  var phoneEl = document.getElementById('phone');
  if (!phoneEl) return;
  phoneEl.addEventListener('input', function (e) {
    var target = e.target;
    var value = target.value.replace(/\D/g, '');
    if (value.length > 3 && value.length <= 6) {
      value = value.slice(0, 3) + '-' + value.slice(3);
    } else if (value.length > 6) {
      value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
    }
    target.value = value;
  });
});

/* ========== File selection and list UI ========== */
var allFiles = [];
onReady(function () {
  var input = document.getElementById('vehicleImages');
  var filesList = document.querySelector('.files-list');
  if (!input || !filesList) return;

  input.addEventListener('change', function () {
    var newFiles = Array.from(this.files || []);
    allFiles = allFiles.concat(newFiles);
    refreshFileList();
  });

  function refreshFileList() {
    filesList.innerHTML = '';
    allFiles.forEach(function (file, index) {
      var fileItem = document.createElement('div');
      fileItem.classList.add('file-item');
      fileItem.innerHTML =
        '<span>' + file.name + ' (' + (file.size / 1024).toFixed(2) + 'KB)</span>' +
        '<button class="delete-btn" data-index="' + index + '" type="button">x</button>';
      fileItem.querySelector('.delete-btn').addEventListener('click', function () {
        var idx = parseInt(this.getAttribute('data-index'), 10);
        allFiles.splice(idx, 1);
        refreshFileList();
      });
      filesList.appendChild(fileItem);
    });
  }
});
