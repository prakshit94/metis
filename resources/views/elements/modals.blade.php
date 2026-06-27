@extends('layouts.app')

@section('title', 'Modals')
@section('page', 'elements-modals')

@section('content')
<div class="container-fluid p-4">
                    
                    <!-- Breadcrumb -->
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./elements.html">Elements</a></li>
                            <li class="breadcrumb-item active">Modals</li>
                        </ol>
                    </nav>

                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0">Modals</h1>
                            <p class="text-muted mb-0">Dialogs with different sizes, animations, and forms</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary" onclick="window.history.back()">
                                <i class="bi bi-arrow-left me-2"></i>Back
                            </button>
                            <button class="btn btn-primary" onclick="copyAllCode()">
                                <i class="bi bi-clipboard me-2"></i>Copy All
                            </button>
                        </div>
                    </div>

                    <!-- Modal Examples -->
                    <div class="row g-4">
                        
                        <!-- Basic Modal -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Basic Modal</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#basicModal">
                                            Launch demo modal
                                        </button>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;!-- Button trigger modal --&gt;
&lt;button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#basicModal"&gt;
  Launch demo modal
&lt;/button&gt;

&lt;!-- Modal --&gt;
&lt;div class="modal fade" id="basicModal" tabindex="-1" aria-labelledby="basicModalLabel" aria-hidden="true"&gt;
  &lt;div class="modal-dialog"&gt;
    &lt;div class="modal-content"&gt;
      &lt;div class="modal-header"&gt;
        &lt;h1 class="modal-title fs-5" id="basicModalLabel"&gt;Modal title&lt;/h1&gt;
        &lt;button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"&gt;&lt;/button&gt;
      &lt;/div&gt;
      &lt;div class="modal-body"&gt;
        Woo-hoo, you're reading this text in a modal!
      &lt;/div&gt;
      &lt;div class="modal-footer"&gt;
        &lt;button type="button" class="btn btn-secondary" data-bs-dismiss="modal"&gt;Close&lt;/button&gt;
        &lt;button type="button" class="btn btn-primary"&gt;Save changes&lt;/button&gt;
      &lt;/div&gt;
    &lt;/div&gt;
  &lt;/div&gt;
&lt;/div&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Sizes -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Modal Sizes</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <button type="button" class="btn btn-primary me-2 mb-2" data-bs-toggle="modal" data-bs-target="#smallModal">
                                            Small modal
                                        </button>
                                        <button type="button" class="btn btn-success me-2 mb-2" data-bs-toggle="modal" data-bs-target="#largeModal">
                                            Large modal
                                        </button>
                                        <button type="button" class="btn btn-info me-2 mb-2" data-bs-toggle="modal" data-bs-target="#extraLargeModal">
                                            Extra large modal
                                        </button>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;!-- Small modal --&gt;
&lt;div class="modal-dialog modal-sm"&gt;
  ...
&lt;/div&gt;

&lt;!-- Large modal --&gt;
&lt;div class="modal-dialog modal-lg"&gt;
  ...
&lt;/div&gt;

&lt;!-- Extra large modal --&gt;
&lt;div class="modal-dialog modal-xl"&gt;
  ...
&lt;/div&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Fullscreen Modal -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Fullscreen Modal</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <button type="button" class="btn btn-primary me-2 mb-2" data-bs-toggle="modal" data-bs-target="#fullscreenModal">
                                            Full screen
                                        </button>
                                        <button type="button" class="btn btn-secondary me-2 mb-2" data-bs-toggle="modal" data-bs-target="#fullscreenSmModal">
                                            Full screen below sm
                                        </button>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;!-- Full screen modal --&gt;
&lt;div class="modal-dialog modal-fullscreen"&gt;
  ...
&lt;/div&gt;

&lt;!-- Full screen below sm --&gt;
&lt;div class="modal-dialog modal-fullscreen-sm-down"&gt;
  ...
&lt;/div&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Vertically Centered Modal -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Vertically Centered</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <button type="button" class="btn btn-primary me-2 mb-2" data-bs-toggle="modal" data-bs-target="#centeredModal">
                                            Vertically centered modal
                                        </button>
                                        <button type="button" class="btn btn-secondary me-2 mb-2" data-bs-toggle="modal" data-bs-target="#scrollableModal">
                                            Vertically centered scrollable modal
                                        </button>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;!-- Vertically centered modal --&gt;
&lt;div class="modal-dialog modal-dialog-centered"&gt;
  ...
&lt;/div&gt;

&lt;!-- Vertically centered scrollable modal --&gt;
&lt;div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"&gt;
  ...
&lt;/div&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Modal with Form -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Modal with Form</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formModal">
                                            Open form modal
                                        </button>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;div class="modal fade" id="formModal" tabindex="-1"&gt;
  &lt;div class="modal-dialog"&gt;
    &lt;div class="modal-content"&gt;
      &lt;div class="modal-header"&gt;
        &lt;h1 class="modal-title fs-5"&gt;New message&lt;/h1&gt;
        &lt;button type="button" class="btn-close" data-bs-dismiss="modal"&gt;&lt;/button&gt;
      &lt;/div&gt;
      &lt;div class="modal-body"&gt;
        &lt;form&gt;
          &lt;div class="mb-3"&gt;
            &lt;label for="recipient-name" class="col-form-label"&gt;Recipient:&lt;/label&gt;
            &lt;input type="text" class="form-control" id="recipient-name"&gt;
          &lt;/div&gt;
          &lt;div class="mb-3"&gt;
            &lt;label for="message-text" class="col-form-label"&gt;Message:&lt;/label&gt;
            &lt;textarea class="form-control" id="message-text"&gt;&lt;/textarea&gt;
          &lt;/div&gt;
        &lt;/form&gt;
      &lt;/div&gt;
      &lt;div class="modal-footer"&gt;
        &lt;button type="button" class="btn btn-secondary" data-bs-dismiss="modal"&gt;Close&lt;/button&gt;
        &lt;button type="button" class="btn btn-primary"&gt;Send message&lt;/button&gt;
      &lt;/div&gt;
    &lt;/div&gt;
  &lt;/div&gt;
&lt;/div&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Static Backdrop Modal -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Static Backdrop</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#staticBackdropModal">
                                            Launch static backdrop modal
                                        </button>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;!-- Button trigger modal --&gt;
&lt;button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#staticBackdropModal"&gt;
  Launch static backdrop modal
&lt;/button&gt;

&lt;!-- Modal --&gt;
&lt;div class="modal fade" id="staticBackdropModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"&gt;
  &lt;div class="modal-dialog"&gt;
    &lt;div class="modal-content"&gt;
      &lt;div class="modal-header"&gt;
        &lt;h1 class="modal-title fs-5"&gt;Modal title&lt;/h1&gt;
        &lt;button type="button" class="btn-close" data-bs-dismiss="modal"&gt;&lt;/button&gt;
      &lt;/div&gt;
      &lt;div class="modal-body"&gt;
        I will not close if you click outside me. Don't even try to press escape key.
      &lt;/div&gt;
      &lt;div class="modal-footer"&gt;
        &lt;button type="button" class="btn btn-secondary" data-bs-dismiss="modal"&gt;Close&lt;/button&gt;
        &lt;button type="button" class="btn btn-primary"&gt;Understood&lt;/button&gt;
      &lt;/div&gt;
    &lt;/div&gt;
  &lt;/div&gt;
&lt;/div&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Scrolling Long Content Modal -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Scrolling Long Content</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <button type="button" class="btn btn-primary me-2 mb-2" data-bs-toggle="modal" data-bs-target="#longContentModal">
                                            Long content modal
                                        </button>
                                        <button type="button" class="btn btn-secondary me-2 mb-2" data-bs-toggle="modal" data-bs-target="#scrollableBodyModal">
                                            Scrollable body modal
                                        </button>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;!-- Scrollable modal --&gt;
&lt;div class="modal-dialog modal-dialog-scrollable"&gt;
  &lt;div class="modal-content"&gt;
    &lt;div class="modal-header"&gt;
      &lt;h1 class="modal-title fs-5"&gt;Modal title&lt;/h1&gt;
      &lt;button type="button" class="btn-close" data-bs-dismiss="modal"&gt;&lt;/button&gt;
    &lt;/div&gt;
    &lt;div class="modal-body"&gt;
      &lt;p&gt;This is some placeholder content to show the scrolling behavior for modals...&lt;/p&gt;
    &lt;/div&gt;
    &lt;div class="modal-footer"&gt;
      &lt;button type="button" class="btn btn-secondary" data-bs-dismiss="modal"&gt;Close&lt;/button&gt;
      &lt;button type="button" class="btn btn-primary"&gt;Save changes&lt;/button&gt;
    &lt;/div&gt;
  &lt;/div&gt;
&lt;/div&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Optional Sizes Demo -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">All Modal Sizes Demo</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <button type="button" class="btn btn-outline-primary me-2 mb-2" data-bs-toggle="modal" data-bs-target="#extraSmallModal">
                                            Extra small modal
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary me-2 mb-2" data-bs-toggle="modal" data-bs-target="#defaultModal">
                                            Default modal
                                        </button>
                                        <button type="button" class="btn btn-outline-success me-2 mb-2" data-bs-toggle="modal" data-bs-target="#mediumModal">
                                            Medium modal
                                        </button>
                                        <button type="button" class="btn btn-outline-warning me-2 mb-2" data-bs-toggle="modal" data-bs-target="#largeModal2">
                                            Large modal
                                        </button>
                                        <button type="button" class="btn btn-outline-danger me-2 mb-2" data-bs-toggle="modal" data-bs-target="#extraLargeModal2">
                                            Extra large modal
                                        </button>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;!-- Extra small modal --&gt;
&lt;div class="modal-dialog modal-sm"&gt;&lt;/div&gt;

&lt;!-- Default modal --&gt;
&lt;div class="modal-dialog"&gt;&lt;/div&gt;

&lt;!-- Large modal --&gt;
&lt;div class="modal-dialog modal-lg"&gt;&lt;/div&gt;

&lt;!-- Extra large modal --&gt;
&lt;div class="modal-dialog modal-xl"&gt;&lt;/div&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
@endsection

@push('modals')
<div class="modal fade" id="basicModal" tabindex="-1" aria-labelledby="basicModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="basicModalLabel">Modal title</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Woo-hoo, you're reading this text in a modal!
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>

<div class="modal fade" id="smallModal" tabindex="-1" aria-labelledby="smallModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="smallModalLabel">Small modal</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    This is a small modal.
                </div>
            </div>
        </div>

<div class="modal fade" id="largeModal" tabindex="-1" aria-labelledby="largeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="largeModalLabel">Large modal</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    This is a large modal with more space for content.
                </div>
            </div>
        </div>

<div class="modal fade" id="extraLargeModal" tabindex="-1" aria-labelledby="extraLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="extraLargeModalLabel">Extra large modal</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    This is an extra large modal with maximum space for content.
                </div>
            </div>
        </div>

<div class="modal fade" id="fullscreenModal" tabindex="-1" aria-labelledby="fullscreenModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="fullscreenModalLabel">Full screen modal</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This is a fullscreen modal that covers the entire viewport.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>

<div class="modal fade" id="fullscreenSmModal" tabindex="-1" aria-labelledby="fullscreenSmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-4" id="fullscreenSmModalLabel">Full screen below sm</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This modal is fullscreen below the sm breakpoint.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>

<div class="modal fade" id="centeredModal" tabindex="-1" aria-labelledby="centeredModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="centeredModalLabel">Vertically centered modal</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This is a vertically centered modal.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>

<div class="modal fade" id="formModal" tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="formModalLabel">New message</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="recipient-name" class="col-form-label">Recipient:</label>
                            <input type="text" class="form-control" id="recipient-name">
                        </div>
                        <div class="mb-3">
                            <label for="message-text" class="col-form-label">Message:</label>
                            <textarea class="form-control" id="message-text"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Send message</button>
                </div>
            </div>
        </div>

<div class="modal fade" id="staticBackdropModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropModalLabel">Modal title</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    I will not close if you click outside me. Don't even try to press escape key.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Understood</button>
                </div>
            </div>
        </div>
@endpush

@push('scripts')
<script type="module" src="./scripts/main.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>

<script>
        function copyCode(button) {
            const codeBlock = button.parentElement.querySelector('.element-code-block pre code');
            navigator.clipboard.writeText(codeBlock.textContent).then(() => {
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="bi bi-check me-2"></i>Copied!';
                button.classList.add('btn-success');
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('btn-success');
                }, 2000);
            });
        }

        function copyAllCode() {
            const allCodeBlocks = document.querySelectorAll('.element-code-block pre code');
            let allCode = '';
            allCodeBlocks.forEach(block => {
                allCode += block.textContent + '\n\n';
            });
            navigator.clipboard.writeText(allCode).then(() => {
                alert('All code copied to clipboard!');
            });
        }

        function initializeSyntaxHighlighting() {
            // Initialize Prism.js highlighting
            if (typeof Prism !== 'undefined') {
                Prism.highlightAll();
            }
        }
            }

            // Apply syntax highlighting
            initializeSyntaxHighlighting();
        });
    </script>
@endpush
