/**
 * Design Cart 3D Carousel
 * Autor: Paweł Nosko → https://www.designcart.pl/pawel-nosko.html
 * Firma: Design Cart → https://www.designcart.pl/
 */

(function ($) {
  "use strict";

  var cfg = window.dc3dCarouselAdmin || {};
  var i18n = cfg.i18n || {};

  function carouselId() {
    return parseInt($("#dc-3d-carousel-id").val(), 10) || 0;
  }

  function setStatus(text, isError) {
    var $el = $("#dc-3d-carousel-upload-status");
    if (!text) {
      $el.attr("hidden", true).text("").removeClass("is-error");
      return;
    }
    $el.removeAttr("hidden").text(text).toggleClass("is-error", !!isError);
  }

  function reindexTiles() {
    $("#dc-3d-carousel-tiles .dc-3d-carousel-tile").each(function (index) {
      var $tile = $(this);
      $tile.find(".dc-3d-carousel-tile__file").attr("name", "images[" + index + "][file]");
      $tile.find(".dc-3d-carousel-tile__alt").attr({
        name: "images[" + index + "][alt]",
        id: "dc-3d-alt-" + index,
      });
      $tile.find(".dc-label").attr("for", "dc-3d-alt-" + index);
    });
  }

  function buildTile(data) {
    var file = data.file || "";
    var url = data.url || "";
    var alt = data.alt || "";
    var $tile = $(
      '<div class="dc-3d-carousel-tile" data-file="">' +
        '<button type="button" class="dc-3d-carousel-tile__handle" title=""><i class="fa fa-bars"></i></button>' +
        '<div class="dc-3d-carousel-tile__thumb"><img src="" alt=""></div>' +
        '<div class="dc-3d-carousel-tile__fields">' +
        '<input type="hidden" class="dc-3d-carousel-tile__file" name="images[0][file]" value="">' +
        '<label class="dc-label"></label>' +
        '<input class="dc-input dc-3d-carousel-tile__alt" type="text" name="images[0][alt]" value="">' +
        "</div>" +
        '<button type="button" class="dc-btn dc-btn--secondary dc-3d-carousel-tile__remove" title=""><i class="fa fa-trash"></i></button>' +
        "</div>"
    );

    $tile.attr("data-file", file);
    $tile.find(".dc-3d-carousel-tile__handle").attr("title", i18n.dragSort || "");
    $tile.find(".dc-3d-carousel-tile__remove").attr("title", i18n.removeImage || "");
    $tile.find(".dc-3d-carousel-tile__thumb img").attr("src", url);
    $tile.find(".dc-3d-carousel-tile__file").val(file);
    $tile.find(".dc-label").text(i18n.alt || "Alt");
    $tile
      .find(".dc-3d-carousel-tile__alt")
      .val(alt)
      .attr("placeholder", i18n.altPlaceholder || "");

    return $tile;
  }

  function uploadFile(file) {
    var id = carouselId();
    if (!id) {
      return $.Deferred().reject().promise();
    }

    var fd = new FormData();
    fd.append("action", "dc_3d_carousel_upload");
    fd.append("nonce", cfg.nonce || "");
    fd.append("carousel_id", String(id));
    fd.append("file", file);

    return $.ajax({
      url: cfg.ajaxUrl,
      method: "POST",
      data: fd,
      processData: false,
      contentType: false,
      dataType: "json",
    });
  }

  function uploadFiles(fileList) {
    var files = Array.prototype.slice.call(fileList || []).filter(function (f) {
      return f && f.type && f.type.indexOf("image/") === 0;
    });
    if (!files.length) return;

    var chain = $.Deferred().resolve().promise();
    var ok = 0;
    var fail = 0;

    setStatus(i18n.uploading || "…");

    files.forEach(function (file) {
      chain = chain.then(function () {
        return uploadFile(file)
          .done(function (resp) {
            if (resp && resp.success && resp.data) {
              $("#dc-3d-carousel-tiles").append(buildTile(resp.data));
              reindexTiles();
              ok += 1;
            } else {
              fail += 1;
            }
          })
          .fail(function () {
            fail += 1;
          });
      });
    });

    chain.always(function () {
      if (fail && !ok) {
        setStatus(i18n.uploadError || "Error", true);
      } else if (fail) {
        setStatus((i18n.uploadDone || "OK") + " (" + ok + "), " + (i18n.uploadError || "Error") + " (" + fail + ")", true);
      } else {
        setStatus(i18n.uploadDone || "OK");
        window.setTimeout(function () {
          setStatus("");
        }, 2500);
      }
    });
  }

  function bindDrop() {
    var $drop = $("#dc-3d-carousel-drop");
    if (!$drop.length) return;

    var $input = $drop.find(".dc-3d-carousel-drop__input");
    var $zone = $drop.find(".dc-3d-carousel-drop__zone");

    $zone.on("click", function (e) {
      if (e.target === $input[0]) return;
      $input.trigger("click");
    });

    $input.on("change", function () {
      uploadFiles(this.files);
      this.value = "";
    });

    $drop.on("dragenter dragover", function (e) {
      e.preventDefault();
      e.stopPropagation();
      $drop.addClass("is-dragover");
    });

    $drop.on("dragleave", function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (!$drop[0].contains(e.relatedTarget)) {
        $drop.removeClass("is-dragover");
      }
    });

    $drop.on("drop", function (e) {
      e.preventDefault();
      e.stopPropagation();
      $drop.removeClass("is-dragover");
      var dt = e.originalEvent && e.originalEvent.dataTransfer;
      if (dt && dt.files) {
        uploadFiles(dt.files);
      }
    });
  }

  function syncRangeValue(range) {
    if (!range || !range.id) return;
    var out = document.querySelector('output.dc-range-value[for="' + range.id + '"]');
    if (!out) return;
    var decimals = parseInt(out.getAttribute("data-decimals") || "0", 10);
    var n = parseFloat(range.value);
    if (!Number.isFinite(n)) {
      out.textContent = range.value;
      return;
    }
    out.textContent = decimals > 0 ? n.toFixed(decimals) : String(Math.round(n));
  }

  $(function () {
    $(document).on("input change", ".dc-range", function () {
      syncRangeValue(this);
    });
    $(".dc-range").each(function () {
      syncRangeValue(this);
    });

    var $tiles = $("#dc-3d-carousel-tiles");

    if ($tiles.length) {
      $tiles.sortable({
        handle: ".dc-3d-carousel-tile__handle",
        placeholder: "dc-3d-carousel-tile-placeholder",
        tolerance: "pointer",
        axis: "y",
        update: reindexTiles,
      });
    }

    bindDrop();

    $tiles.on("click", ".dc-3d-carousel-tile__remove", function () {
      if (!window.confirm(i18n.confirmRemoveImage || "Delete?")) {
        return;
      }

      var $tile = $(this).closest(".dc-3d-carousel-tile");
      var file = $tile.attr("data-file") || $tile.find(".dc-3d-carousel-tile__file").val();
      var id = carouselId();

      $.post(cfg.ajaxUrl, {
        action: "dc_3d_carousel_delete_image",
        nonce: cfg.nonce,
        carousel_id: id,
        file: file,
      })
        .done(function (resp) {
          if (resp && resp.success) {
            $tile.remove();
            reindexTiles();
          } else {
            window.alert((resp && resp.data && resp.data.message) || i18n.uploadError || "Error");
          }
        })
        .fail(function () {
          window.alert(i18n.uploadError || "Error");
        });
    });

    $(document).on("click", ".dc-3d-carousel-delete", function (e) {
      if (!window.confirm(i18n.confirmDelete || "Delete?")) {
        e.preventDefault();
      }
    });
  });
})(jQuery);
