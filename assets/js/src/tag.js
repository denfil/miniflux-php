Miniflux.Tag = (function() {
    function _on(element, eventName, handler) {
        element.addEventListener(eventName, handler, false);
    }

    function _trim(str) {
        return str.replace(/^\s+|\s+$/g, "");
    }

    function _post(url, data, success) {
        var request = new XMLHttpRequest();
        request.onload = function() {
            if (this.readyState === 4 && this.status === 200) {
                success(this.responseText);
            }
        };
        request.open("POST", url, true);
        request.setRequestHeader("Content-Type", "application/json");
        request.send(JSON.stringify(data));
    }

    var TagList = function (container) {
        this.itemId = container.getAttribute("data-item-id");
        this.tagsContainer = container.querySelector(".tag-list");
        this.tags = this.parseTags();
        this.editCntrl = container.querySelector(".tags-link-edit");
        this.addCntrl = container.querySelector(".item-tag-add");
        this.tagInput = this.addCntrl.querySelector("input");

        var cancelCntrl = this.addCntrl.querySelector(".tags-link-cancel");

        var suggest;

        var self = this,

            enableEditMode = function () {
                self.editCntrl.style.display = 'none';
                self.addCntrl.style.display = 'inline-block';
                self.tagsContainer.innerHTML = "";
                self.tagInput.value = "";
                self.tagInput.focus();
                suggest = new autoComplete({
                    selector: self.tagInput,
                    minChars: 0,
                    menuClass: "tags-suggestion",
                    source: function(term, response) {
                        _post("?action=search-tag", {text: term}, function (data) {
                            response(JSON.parse(data));
                        });
                    },
                    onSelect: function(e, term, item) {
                        self.tagInput.value = term;
                        addTag(e);
                    }
                });
                if (self.tags.length === 0) {
                    return;
                }
                var result = "";
                for (var i = 0, c = self.tags.length; i < c; i++) {
                    result += '<div data-tag-id="' + self.tags[i].id + '">' + self.tags[i].title + '<span class="item-tag-remove"></span></div>';
                }
                self.tagsContainer.innerHTML = result;
                var removeLinks = self.tagsContainer.querySelectorAll(".item-tag-remove");
                for (var idx = 0, cnt = removeLinks.length; idx < cnt; idx++) {
                    _on(removeLinks[idx], "click", removeTag);
                }
            },

            disableEditMode = function () {
                suggest.destroy();
                self.editCntrl.style.display = 'inline-block';
                self.addCntrl.style.display = 'none';
                self.tagsContainer.innerHTML = "";
                if (self.tags.length === 0) {
                    return;
                }
                var result = '<ul>';
                for (var i = 0, cnt = self.tags.length; i < cnt; i++) {
                    result += '<li><a href="?action=search-tag&tag_id=' + self.tags[i].id + '">' + self.tags[i].title + '</a></li> ';
                }
                result += "</ul>";
                self.tagsContainer.innerHTML = result;
            },

            addTag = function (e) {
                var keyCode = e.keyCode ? e.keyCode : e.which;
                if (keyCode == 27) { // Esc
                    disableEditMode();
                    return;
                }
                if (keyCode != 13) { // Enter
                    return;
                }
                self.tagInput.setAttribute("disabled", "disabled");
                var data = {
                    "item_id": self.itemId,
                    "tag_title": _trim(this.value)
                };
                _post("?action=add-item-tag", data, function (result) {
                    self.tagInput.removeAttribute("disabled");
                    var response = JSON.parse(result);
                    if (response.tags) {
                        self.tags = response.tags;
                        enableEditMode();
                    }
                });
            },

            removeTag = function () {
                var data = {
                        "item_id": self.itemId,
                        "tag_id": this.parentNode.getAttribute("data-tag-id")
                    };
                _post("?action=remove-item-tag", data, function (result) {
                    var response = JSON.parse(result);
                    if (response.tags) {
                        self.tags = response.tags;
                        enableEditMode();
                    }
                });
            };

        _on(this.editCntrl, "click", enableEditMode);
        _on(this.tagInput, "keydown", addTag);
        _on(cancelCntrl, "click", disableEditMode);
    };

    TagList.prototype.parseTags = function () {
        var result = [],
            tagLinks = this.tagsContainer.querySelectorAll("a");
        if (tagLinks.length === 0) {
            return result;
        }
        for (var i = 0, cnt = tagLinks.length; i < cnt; i++) {
            result.push({
                "id": tagLinks[i].getAttribute("data-tag-id"),
                "title": tagLinks[i].innerHTML
            });
        }
        return result;
    };

    return {
        Init: function () {
            var tagList = document.querySelectorAll(".item-tags");
            if (tagList.length !== 0) {
                for (var i = 0, cnt = tagList.length; i < cnt; i++) {
                    new TagList(tagList[i]);
                }
            }
        }
    };
})();
