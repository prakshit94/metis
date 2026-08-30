@extends('layouts.app')

@section('title', 'Buttons')
@section('page', 'elements-buttons')

@section('content')
<div class="container-fluid p-4">
                    
                    <!-- Breadcrumb -->
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./elements.html">Elements</a></li>
                            <li class="breadcrumb-item active">Buttons</li>
                        </ol>
                    </nav>

                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0">Buttons</h1>
                            <p class="text-muted mb-0">Bootstrap button styles, sizes, and states</p>
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

                    <!-- Button Examples -->
                    <div class="row g-4">
                        
                        <!-- Basic Button Styles -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Basic Button Styles</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <button type="button" class="btn btn-primary me-2 mb-2">Primary</button>
                                        <button type="button" class="btn btn-secondary me-2 mb-2">Secondary</button>
                                        <button type="button" class="btn btn-success me-2 mb-2">Success</button>
                                        <button type="button" class="btn btn-danger me-2 mb-2">Danger</button>
                                        <button type="button" class="btn btn-warning me-2 mb-2">Warning</button>
                                        <button type="button" class="btn btn-info me-2 mb-2">Info</button>
                                        <button type="button" class="btn btn-outline-secondary me-2 mb-2">Light</button>
                                        <button type="button" class="btn btn-dark me-2 mb-2">Dark</button>
                                        <button type="button" class="btn btn-link me-2 mb-2">Link</button>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;button type="button" class="btn btn-primary"&gt;Primary&lt;/button&gt;
&lt;button type="button" class="btn btn-secondary"&gt;Secondary&lt;/button&gt;
&lt;button type="button" class="btn btn-success"&gt;Success&lt;/button&gt;
&lt;button type="button" class="btn btn-danger"&gt;Danger&lt;/button&gt;
&lt;button type="button" class="btn btn-warning"&gt;Warning&lt;/button&gt;
&lt;button type="button" class="btn btn-info"&gt;Info&lt;/button&gt;
&lt;button type="button" class="btn btn-outline-secondary"&gt;Light&lt;/button&gt;
&lt;button type="button" class="btn btn-dark"&gt;Dark&lt;/button&gt;
&lt;button type="button" class="btn btn-link"&gt;Link&lt;/button&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Outline Buttons -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Outline Buttons</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <button type="button" class="btn btn-outline-primary me-2 mb-2">Primary</button>
                                        <button type="button" class="btn btn-outline-secondary me-2 mb-2">Secondary</button>
                                        <button type="button" class="btn btn-outline-success me-2 mb-2">Success</button>
                                        <button type="button" class="btn btn-outline-danger me-2 mb-2">Danger</button>
                                        <button type="button" class="btn btn-outline-warning me-2 mb-2">Warning</button>
                                        <button type="button" class="btn btn-outline-info me-2 mb-2">Info</button>
                                        <button type="button" class="btn btn-outline-light me-2 mb-2">Light</button>
                                        <button type="button" class="btn btn-outline-dark me-2 mb-2">Dark</button>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;button type="button" class="btn btn-outline-primary"&gt;Primary&lt;/button&gt;
&lt;button type="button" class="btn btn-outline-secondary"&gt;Secondary&lt;/button&gt;
&lt;button type="button" class="btn btn-outline-success"&gt;Success&lt;/button&gt;
&lt;button type="button" class="btn btn-outline-danger"&gt;Danger&lt;/button&gt;
&lt;button type="button" class="btn btn-outline-warning"&gt;Warning&lt;/button&gt;
&lt;button type="button" class="btn btn-outline-info"&gt;Info&lt;/button&gt;
&lt;button type="button" class="btn btn-outline-light"&gt;Light&lt;/button&gt;
&lt;button type="button" class="btn btn-outline-dark"&gt;Dark&lt;/button&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Button Sizes -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Button Sizes</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <button type="button" class="btn btn-primary btn-lg me-2 mb-2">Large button</button>
                                        <button type="button" class="btn btn-secondary btn-lg me-2 mb-2">Large button</button>
                                        <br>
                                        <button type="button" class="btn btn-primary me-2 mb-2">Default button</button>
                                        <button type="button" class="btn btn-secondary me-2 mb-2">Default button</button>
                                        <br>
                                        <button type="button" class="btn btn-primary btn-sm me-2 mb-2">Small button</button>
                                        <button type="button" class="btn btn-secondary btn-sm me-2 mb-2">Small button</button>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;button type="button" class="btn btn-primary btn-lg"&gt;Large button&lt;/button&gt;
&lt;button type="button" class="btn btn-primary"&gt;Default button&lt;/button&gt;
&lt;button type="button" class="btn btn-primary btn-sm"&gt;Small button&lt;/button&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Button States -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Button States</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <button type="button" class="btn btn-primary me-2 mb-2">Normal</button>
                                        <button type="button" class="btn btn-primary active me-2 mb-2">Active</button>
                                        <button type="button" class="btn btn-primary me-2 mb-2" disabled>Disabled</button>
                                        <br>
                                        <button type="button" class="btn btn-primary me-2 mb-2">
                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                            Loading...
                                        </button>
                                        <button type="button" class="btn btn-success me-2 mb-2">
                                            <i class="bi bi-check-circle me-2"></i>Success
                                        </button>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;button type="button" class="btn btn-primary"&gt;Normal&lt;/button&gt;
&lt;button type="button" class="btn btn-primary active"&gt;Active&lt;/button&gt;
&lt;button type="button" class="btn btn-primary" disabled&gt;Disabled&lt;/button&gt;

&lt;!-- Loading button --&gt;
&lt;button type="button" class="btn btn-primary"&gt;
    &lt;span class="spinner-border spinner-border-sm me-2"&gt;&lt;/span&gt;
    Loading...
&lt;/button&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Button Groups -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Button Groups</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <div class="btn-group mb-3" role="group">
                                            <button type="button" class="btn btn-primary">Left</button>
                                            <button type="button" class="btn btn-primary">Middle</button>
                                            <button type="button" class="btn btn-primary">Right</button>
                                        </div>
                                        <br>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-primary">Left</button>
                                            <button type="button" class="btn btn-outline-primary">Middle</button>
                                            <button type="button" class="btn btn-outline-primary">Right</button>
                                        </div>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;div class="btn-group" role="group"&gt;
    &lt;button type="button" class="btn btn-primary"&gt;Left&lt;/button&gt;
    &lt;button type="button" class="btn btn-primary"&gt;Middle&lt;/button&gt;
    &lt;button type="button" class="btn btn-primary"&gt;Right&lt;/button&gt;
&lt;/div&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Icon Buttons -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Icon Buttons</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <button type="button" class="btn btn-primary me-2 mb-2">
                                            <i class="bi bi-plus-lg me-2"></i>Add New
                                        </button>
                                        <button type="button" class="btn btn-success me-2 mb-2">
                                            <i class="bi bi-check-circle me-2"></i>Save
                                        </button>
                                        <button type="button" class="btn btn-danger me-2 mb-2">
                                            <i class="bi bi-trash me-2"></i>Delete
                                        </button>
                                        <br>
                                        <button type="button" class="btn btn-outline-primary me-2 mb-2">
                                            <i class="bi bi-download"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary me-2 mb-2">
                                            <i class="bi bi-share"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-warning me-2 mb-2">
                                            <i class="bi bi-star"></i>
                                        </button>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;button type="button" class="btn btn-primary"&gt;
    &lt;i class="bi bi-plus-lg me-2"&gt;&lt;/i&gt;Add New
&lt;/button&gt;

&lt;!-- Icon only buttons --&gt;
&lt;button type="button" class="btn btn-outline-primary"&gt;
    &lt;i class="bi bi-download"&gt;&lt;/i&gt;
&lt;/button&gt;</code></pre>
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

@push('scripts')


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
                // Show success notification
                alert('All code copied to clipboard!');
            });
        }

        function initializeSyntaxHighlighting() {
            // Initialize Prism.js highlighting
            if (typeof Prism !== 'undefined') {
                Prism.highlightAll();
            }
        }

            // Apply syntax highlighting
            document.addEventListener('DOMContentLoaded', initializeSyntaxHighlighting);
    </script>
@endpush
