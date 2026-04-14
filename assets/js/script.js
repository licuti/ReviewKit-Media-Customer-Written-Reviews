(function($) { // Bọc code trong hàm ẩn danh để dùng $ an toàn
    $(document).ready(function() {
        console.log('MCWR: Script Loaded (jQuery Ready)');

        // --- KHAI BÁO BIẾN CHUNG ---
        const dropzone = document.getElementById('mcwr_dropzone');
        const fileInput = document.getElementById('mcwr_file_input');
        const previewList = document.getElementById('mcwr_preview_list');
        const form = document.getElementById('mcwr_form');
        
        // Biến cho phần Upload Form
        let filesStore = []; // Kho chứa file upload

        // Lấy settings và i18n từ localize script
        const mcwr = (typeof mcwr_vars !== 'undefined') ? mcwr_vars : { i18n: {} };
        const i18n = mcwr.i18n || {};

        // Helper function for i18n with simple placeholder replacement
        function __(key, ...args) {
            let str = i18n[key] || key;
            args.forEach((val, i) => {
                str = str.replace(/%[sd]/, val);
            });
            return str;
        }

        // ======================================================
        // PHẦN 1: UPLOAD ẢNH & VIDEO & FORM SUBMIT
        // ======================================================

        if (dropzone && fileInput && form) {
            
            // 1.1. Xử lý Drag & Drop
            dropzone.addEventListener('click', () => fileInput.click());

            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropzone.classList.add('drag-over');
            });
            dropzone.addEventListener('dragleave', () => dropzone.classList.remove('drag-over'));
            
            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropzone.classList.remove('drag-over');
                handleFiles(e.dataTransfer.files);
            });

            fileInput.addEventListener('change', (e) => {
                handleFiles(e.target.files);
                fileInput.value = ''; 
            });

            // 1.2. Logic xử lý file
            function handleFiles(newFiles) {
                if (!newFiles || newFiles.length === 0) return;

                // Check quyền upload từ Admin
                if (mcwr.allow_upload !== 'yes') {
                    alert(__('upload_locked'));
                    return;
                }

                let newFilesArray = Array.from(newFiles);
                
                const maxFiles = parseInt(mcwr.max_files) || 5;
                const maxSizeMB = parseInt(mcwr.max_size_mb) || 2;
                const maxVideoSizeMB = parseInt(mcwr.max_video_mb) || 10;
                
                const allowedVideoExtensions = (mcwr.allowed_video || 'mp4,webm,mov').split(',');

                const currentCount = filesStore.length;
                const availableSlots = maxFiles - currentCount;

                if (availableSlots <= 0) {
                    alert(__('max_files_reached', maxFiles));
                    return;
                }

                if (newFilesArray.length > availableSlots) {
                    alert(__('extra_images_limit', availableSlots));
                    newFilesArray = newFilesArray.slice(0, availableSlots);
                }

                newFilesArray.forEach(file => {
                    const isImage = file.type.match('image.*');
                    const isVideo = file.type.match('video.*');
                    const ext = file.name.split('.').pop().toLowerCase();

                    if (isImage) {
                        if (file.size > (maxSizeMB * 1024 * 1024)) {
                            alert(__('file_too_large', file.name, maxSizeMB));
                        } else {
                            filesStore.push(file);
                        }
                    } else if (isVideo) {
                        if (mcwr.enable_video !== 'yes') {
                            alert('Tính năng upload video hiện đang tắt.');
                            return;
                        }
                        if (!allowedVideoExtensions.includes(ext)) {
                            alert('Định dạng video không hỗ trợ: ' + ext);
                            return;
                        }
                        if (file.size > (maxVideoSizeMB * 1024 * 1024)) {
                            alert(__('file_too_large', file.name, maxVideoSizeMB));
                        } else {
                            filesStore.push(file);
                        }
                    }
                });
                renderPreview();
            }

            // 1.3. Render giao diện xem trước
            function renderPreview() {
                if (!previewList) return;
                previewList.innerHTML = '';
                
                filesStore.forEach((file, index) => {
                    const item = document.createElement('div');
                    item.className = 'mcwr-preview-item';
                    item.draggable = true;
                    item.dataset.index = index;

                    const removeBtn = document.createElement('div');
                    removeBtn.className = 'mcwr-remove-btn';
                    removeBtn.innerHTML = '×';
                    removeBtn.onclick = (e) => {
                        e.stopPropagation();
                        removeFile(index);
                    };

                    item.appendChild(removeBtn);

                    if (file.type.match('image.*')) {
                        const reader = new FileReader();
                        const img = document.createElement('img');
                        reader.onload = (e) => { img.src = e.target.result; };
                        reader.readAsDataURL(file);
                        item.appendChild(img);
                    } else if (file.type.match('video.*')) {
                        // --- Video Thumbnail Generator via HTML5 Canvas ---
                        const videoEl  = document.createElement('video');
                        const canvas   = document.createElement('canvas');
                        const ctx      = canvas.getContext('2d');
                        const thumbImg = document.createElement('img');

                        const objUrl = URL.createObjectURL(file);
                        videoEl.src  = objUrl;
                        videoEl.preload = 'metadata';
                        videoEl.muted = true;

                        videoEl.addEventListener('loadeddata', function() {
                            // Seek đến giây thứ 1 (hoặc cuối video nếu quá ngắn)
                            videoEl.currentTime = Math.min(1, videoEl.duration * 0.1);
                        });

                        videoEl.addEventListener('seeked', function() {
                            canvas.width  = videoEl.videoWidth  || 200;
                            canvas.height = videoEl.videoHeight || 150;
                            ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);
                            thumbImg.src = canvas.toDataURL('image/jpeg');
                            URL.revokeObjectURL(objUrl); // Giải phóng bộ nhớ
                        });

                        // Fallback nếu không lấy được thumbnail
                        thumbImg.onerror = () => {
                            item.classList.add('is-video-preview');
                            const videoIcon = document.createElement('div');
                            videoIcon.className = 'mcwr-video-preview-icon';
                            // Dùng dashicons class — font đã được enqueue bởi plugin.
                            videoIcon.innerHTML = '<i class="dashicons dashicons-video-alt3" aria-hidden="true"></i>';
                            item.appendChild(videoIcon);
                        };

                        // Play overlay icon trên thumb
                        const playOverlay = document.createElement('div');
                        playOverlay.className = 'mcwr-thumb-play-overlay';
                        playOverlay.innerHTML = '▶';

                        item.style.position = 'relative';
                        item.style.overflow = 'hidden';
                        item.appendChild(thumbImg);
                        item.appendChild(playOverlay);

                        const fileNameLabel = document.createElement('span');
                        fileNameLabel.className = 'file-name-hint';
                        fileNameLabel.innerText = file.name.length > 10 ? file.name.substring(0, 7) + '...' : file.name;
                        item.appendChild(fileNameLabel);
                    } // end else if video

                    previewList.appendChild(item);
                    addDragSortEvents(item);
                });
            }

            function removeFile(index) {
                filesStore.splice(index, 1);
                renderPreview();
            }

            // 1.4. Sắp xếp (Drag Sort)
            let dragStartIndex;
            function addDragSortEvents(item) {
                item.addEventListener('dragstart', function() {
                    dragStartIndex = +this.dataset.index;
                    this.classList.add('dragging');
                });
                item.addEventListener('dragover', (e) => e.preventDefault());
                item.addEventListener('drop', function() {
                    const dragEndIndex = +this.dataset.index;
                    swapFiles(dragStartIndex, dragEndIndex);
                    this.classList.remove('dragging');
                });
                item.addEventListener('dragend', function() { this.classList.remove('dragging'); });
            }

            function swapFiles(fromIndex, toIndex) {
                if (fromIndex === toIndex) return;
                const itemToMove = filesStore[fromIndex];
                filesStore.splice(fromIndex, 1);
                filesStore.splice(toIndex, 0, itemToMove);
                renderPreview();
            }

            // 1.5. Xử lý Submit Form
            form.addEventListener('submit', function(e) {
                console.log('MCWR: Đang chuẩn bị gửi form...');
                const dataTransfer = new DataTransfer();
                filesStore.forEach(file => {
                    dataTransfer.items.add(file);
                });
                fileInput.files = dataTransfer.files;
            });
        }


        // ======================================================
        // PHẦN 2: TƯƠNG TÁC (LIKE & REPLY)
        // ======================================================

        // 2.1. Nút Like/Hữu ích (Delegation)
        $(document).on('click', '.like-btn', function(e) {
            e.preventDefault();
            const btn = $(this);

            if (btn.hasClass('loading') || btn.hasClass('voted')) return;

            const commentId = btn.data('comment-id');
            const countSpan = btn.find('.count');

            btn.addClass('loading').css('opacity', '0.7');

            $.ajax({
                url: mcwr.ajax_url,
                type: 'POST',
                data: {
                    action: 'mcwr_vote_review',
                    comment_id: commentId,
                    nonce: mcwr.nonce
                },
                success: function(response) {
                    btn.removeClass('loading').css('opacity', '1');
                    if (response.success) {
                        countSpan.text(response.data.new_count);
                        btn.addClass('voted').css({'color': '#0073aa', 'font-weight': 'bold'});
                    } else {
                        alert(response.data.message || 'Error occurred');
                    }
                },
                error: function() {
                    btn.removeClass('loading').css('opacity', '1');
                    alert(__('connection_error'));
                }
            });
        });

        // 2.2. Nút Reply Admin (Delegation)
        $(document).on('click', '.reply-toggle-btn', function(e) {
            e.preventDefault();
            const btn = $(this);
            const commentId = btn.data('comment-id');
            const replyForm = $('#reply-form-' + commentId);
            
            if (replyForm.length) {
                replyForm.slideToggle();
                replyForm.find('textarea').focus();
            }
        });


        // ======================================================
        // PHẦN 3: FILTER, SORT & PAGINATION (AJAX) — Hỗ trợ multi-instance
        // ======================================================

        /**
         * Khởi tạo bộ lọc/sắp xếp/phân trang cho một block review.
         * Mỗi `.mcwr-filter-container` là một instance độc lập.
         */
        function initReviewBlock(filterContainer) {
            const $container  = $(filterContainer);
            const productId   = $container.data('product-id');

            // Tìm wrapper danh sách của cùng product_id
            const $listWrapper = $('.mcwr-reviews-wrapper[data-product-id="' + productId + '"]');
            if (!$listWrapper.length) return;

            // Tìm sort dropdown TRONG container này
            const $sortSelect = $container.find('.mcwr-sort-dropdown');

            // ─── Hàm gọi AJAX trung tâm ────────────────────────────
            function triggerReviewUpdate(page, append, loadBtn) {
                page   = page  || 1;
                append = append || false;

                const activeBtn    = $container.find('.filter-btn.active');
                const filterType   = activeBtn.data('filter') || 'all';
                const sortType     = $sortSelect.val() || 'newest';

                if (!append) {
                    $listWrapper.css({ 'opacity': '0.5', 'pointer-events': 'none' });
                } else if (loadBtn) {
                    $(loadBtn).prop('disabled', true).text(__('loading') || 'Đang tải...');
                }

                $.ajax({
                    url: mcwr.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'mcwr_filter_reviews',
                        product_id: productId,      // ← đọc từ data attribute, KHÔNG phải mcwr.product_id toàn cục
                        filter_type: filterType,
                        sort_type: sortType,
                        page: page
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            if (append) {
                                $listWrapper.find('.mcwr-load-more-container').remove();
                                $listWrapper.append(response.data.html);
                            } else {
                                $listWrapper.html(response.data.html);

                                // Scroll lên đầu toolbar
                                $('html, body').animate({
                                    scrollTop: $container.offset().top - 100
                                }, 400);
                            }

                            // Đồng bộ dropdown nếu server trả về sort type
                            if ($sortSelect.length && response.data.final_sort_type) {
                                $sortSelect.val(response.data.final_sort_type);
                            }

                            // Re-bind pagination & load more trong HTML mới trả về
                            bindListEvents($listWrapper, productId, triggerReviewUpdate);

                        } else {
                            if (!append) {
                                $listWrapper.html('<p class="mcwr-no-review">' + (__('no_results') || 'Không có kết quả nào.') + '</p>');
                            }
                        }
                    },
                    error: function(xhr) {
                        console.error('MCWR AJAX error:', xhr);
                        alert(__('connection_error') || 'Lỗi kết nối.');
                    },
                    complete: function() {
                        $listWrapper.css({ 'opacity': '1', 'pointer-events': 'auto' });
                        if (loadBtn) {
                            $(loadBtn).prop('disabled', false).text(__('load_more') || 'Tải thêm đánh giá');
                        }
                    }
                });
            }

            // ─── Gắn sự kiện Filter Buttons ────────────────────────
            $container.on('click', '.filter-btn', function(e) {
                e.preventDefault();
                $container.find('.filter-btn').removeClass('active');
                $(this).addClass('active');

                const filterType = $(this).data('filter');
                // Nếu lọc theo sao, reset sort về mặc định
                if (!isNaN(parseInt(filterType)) && parseInt(filterType) >= 1 && parseInt(filterType) <= 5) {
                    if ($sortSelect.length) $sortSelect.val('newest');
                }
                triggerReviewUpdate(1, false);
            });

            // ─── Gắn sự kiện Sort Dropdown ─────────────────────────
            $sortSelect.on('change', function() {
                triggerReviewUpdate(1, false);
            });

            // ─── Gắn events phân trang & load-more vào list wrapper ─
            bindListEvents($listWrapper, productId, triggerReviewUpdate);
        }

        /**
         * Bind phân trang số và Load More vào wrapper
         * (Gọi lại sau mỗi lần AJAX thay thế HTML)
         */
        function bindListEvents($wrapper, productId, triggerFn) {
            // Phân trang số (dùng event delegation nên không cần bind lại)
            $wrapper.off('click.mcwr-page').on('click.mcwr-page', '.page-btn', function(e) {
                e.preventDefault();
                triggerFn($(this).data('page'), false);
            });

            // Load More
            $wrapper.off('click.mcwr-more').on('click.mcwr-more', '.mcwr-load-more-btn, #mcwr-load-more-btn', function(e) {
                e.preventDefault();
                const nextPage = $(this).data('page');
                triggerFn(nextPage, true, this);
            });
        }

        // Khởi tạo tất cả block review trên trang hiện tại
        $('.mcwr-filter-container').each(function() {
            initReviewBlock(this);
        });


        // --- 4. KHỞI TẠO FANCYBOX ---
        if (typeof Fancybox !== 'undefined') {

            const isToolbarEnabled = (mcwr.lb_toolbar === 'yes');

            // '' (chuỗi rỗng) có nghĩa là tắt thumbnails – không dùng || để tránh mất ''
            const layoutSetting = (mcwr.lb_layout !== undefined && mcwr.lb_layout !== null)
                ? mcwr.lb_layout   // 'modern' | 'classic' | 'scrollable' | 'vertical' | ''
                : 'modern';

            // Toolbar buttons
            const toolbarButtons = isToolbarEnabled
                ? { right: ['zoom', 'slideShow', 'fullscreen', 'download', 'thumbs', 'close'] }
                : { right: ['close'] };

            // Xây dựng config đúng theo Fancybox v4 docs:
            // Thumbnail plugin nằm bên trong Carousel.Thumbs
            const fancyboxConfig = {
                theme: mcwr.lb_theme || 'dark',
                Toolbar: {
                    display: toolbarButtons,
                },
                Image: {
                    Panzoom: { maxScale: 2 },
                },
                Carousel: {},
            };

            if (layoutSetting === '') {
                // Tắt hoàn toàn thumbnails
                fancyboxConfig.Carousel.Thumbs = false;
            } else if (layoutSetting === 'vertical') {
                // Thumbnails dọc (chỉ khi gallery có >=2 ảnh, CSS đã hỗ trợ sẵn)
                fancyboxConfig.Carousel.Thumbs = {
                    type: 'scrollable',
                };
                // CSS trong style.css đã có rule chuyển thumbs sang cột dọc
            } else {
                // 'modern' | 'classic' | 'scrollable' – truyền thẳng
                fancyboxConfig.Carousel.Thumbs = {
                    type: layoutSetting, // Đúng API Fancybox v4
                };
            }

            Fancybox.bind('.review-gallery-item', fancyboxConfig);
        }





        // ======================================================
        // PHẦN 5: HIỆU ỨNG PROGRESS BAR
        // ======================================================
        
        const progressBars = document.querySelectorAll('.mcwr-progress-bar');
        if (progressBars.length > 0) {
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1 
            };

            const progressObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const bar = entry.target;
                        const targetWidth = bar.getAttribute('data-percent');
                        bar.style.width = targetWidth;
                        observer.unobserve(bar);
                    }
                });
            }, observerOptions);

            progressBars.forEach(bar => {
                progressObserver.observe(bar);
            });
        }

        // ======================================================
        // PHẦN 6: REPORT REVIEW (Báo cáo đánh giá)
        // ======================================================

        $(document).on('click', '.mcwr-report-btn', function(e) {
            e.preventDefault();
            const btn       = $(this);
            const commentId = btn.data('comment-id');

            if (btn.hasClass('mcwr-reported')) return;

            if (!confirm('Bạn có chắc muốn báo cáo đánh giá này là vi phạm không?')) return;

            const reason = prompt('Lý do báo cáo (tuỳ chọn):') || '';

            btn.prop('disabled', true).text('Đang gửi...');

            $.ajax({
                url: mcwr.ajax_url,
                type: 'POST',
                data: {
                    action: 'mcwr_report_review',
                    comment_id: commentId,
                    reason: reason,
                    nonce: mcwr.nonce,
                },
                success: function(response) {
                    if (response.success) {
                        btn.text('✅ Đã báo cáo').addClass('mcwr-reported').prop('disabled', true);
                    } else {
                        alert(response.data.message || 'Đã xảy ra lỗi.');
                        btn.prop('disabled', false).text('🚩 Báo cáo');
                    }
                },
                error: function() {
                    alert('Lỗi kết nối.');
                    btn.prop('disabled', false).text('🚩 Báo cáo');
                }
            });
        });

    }); 
})(jQuery);