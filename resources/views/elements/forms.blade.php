@extends('layouts.app')

@section('title', 'Forms')
@section('page', 'elements-forms')

@section('content')
<div class="container-fluid p-4">
                    
                    <!-- Breadcrumb -->
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./elements.html">Elements</a></li>
                            <li class="breadcrumb-item active">Forms</li>
                        </ol>
                    </nav>

                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0">Forms</h1>
                            <p class="text-muted mb-0">Form controls, validation, input groups and layouts</p>
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

                    <!-- Form Examples -->
                    <div class="row g-4">
                        
                        <!-- Basic Form Controls -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Basic Form Controls</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <form>
                                            <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label">Email address</label>
                                                <input type="email" class="form-control" id="exampleFormControlInput1" placeholder="name@example.com">
                                            </div>
                                            <div class="mb-3">
                                                <label for="exampleFormControlTextarea1" class="form-label">Example textarea</label>
                                                <textarea class="form-control" id="exampleFormControlTextarea1" rows="3"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label for="formFile" class="form-label">Default file input example</label>
                                                <input class="form-control" type="file" id="formFile">
                                            </div>
                                        </form>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;div class="mb-3"&gt;
  &lt;label for="exampleFormControlInput1" class="form-label"&gt;Email address&lt;/label&gt;
  &lt;input type="email" class="form-control" id="exampleFormControlInput1" placeholder="name@example.com"&gt;
&lt;/div&gt;
&lt;div class="mb-3"&gt;
  &lt;label for="exampleFormControlTextarea1" class="form-label"&gt;Example textarea&lt;/label&gt;
  &lt;textarea class="form-control" id="exampleFormControlTextarea1" rows="3"&gt;&lt;/textarea&gt;
&lt;/div&gt;
&lt;div class="mb-3"&gt;
  &lt;label for="formFile" class="form-label"&gt;Default file input example&lt;/label&gt;
  &lt;input class="form-control" type="file" id="formFile"&gt;
&lt;/div&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Form Sizing -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Form Sizing</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <input class="form-control form-control-lg mb-3" type="text" placeholder=".form-control-lg" aria-label=".form-control-lg example">
                                        <input class="form-control mb-3" type="text" placeholder="Default input" aria-label="default input example">
                                        <input class="form-control form-control-sm" type="text" placeholder=".form-control-sm" aria-label=".form-control-sm example">
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;input class="form-control form-control-lg" type="text" placeholder=".form-control-lg"&gt;
&lt;input class="form-control" type="text" placeholder="Default input"&gt;
&lt;input class="form-control form-control-sm" type="text" placeholder=".form-control-sm"&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Disabled Forms -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Disabled Forms</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <input class="form-control mb-3" type="text" placeholder="Disabled input" aria-label="Disabled input example" disabled>
                                        <input class="form-control mb-3" type="text" value="Disabled readonly input" aria-label="Disabled input example" disabled readonly>
                                        <select class="form-select" aria-label="Disabled select example" disabled>
                                            <option selected>Open this select menu</option>
                                            <option value="1">One</option>
                                            <option value="2">Two</option>
                                            <option value="3">Three</option>
                                        </select>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;input class="form-control" type="text" placeholder="Disabled input" disabled&gt;
&lt;input class="form-control" type="text" value="Disabled readonly input" disabled readonly&gt;
&lt;select class="form-select" disabled&gt;
  &lt;option selected&gt;Open this select menu&lt;/option&gt;
  &lt;option value="1"&gt;One&lt;/option&gt;
  &lt;option value="2"&gt;Two&lt;/option&gt;
  &lt;option value="3"&gt;Three&lt;/option&gt;
&lt;/select&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Select -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Select</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <select class="form-select mb-3" aria-label="Default select example">
                                            <option selected>Open this select menu</option>
                                            <option value="1">One</option>
                                            <option value="2">Two</option>
                                            <option value="3">Three</option>
                                        </select>
                                        <select class="form-select form-select-lg mb-3" aria-label="Large select example">
                                            <option selected>Open this select menu</option>
                                            <option value="1">One</option>
                                            <option value="2">Two</option>
                                            <option value="3">Three</option>
                                        </select>
                                        <select class="form-select form-select-sm" aria-label="Small select example">
                                            <option selected>Open this select menu</option>
                                            <option value="1">One</option>
                                            <option value="2">Two</option>
                                            <option value="3">Three</option>
                                        </select>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;select class="form-select" aria-label="Default select example"&gt;
  &lt;option selected&gt;Open this select menu&lt;/option&gt;
  &lt;option value="1"&gt;One&lt;/option&gt;
  &lt;option value="2"&gt;Two&lt;/option&gt;
  &lt;option value="3"&gt;Three&lt;/option&gt;
&lt;/select&gt;
&lt;select class="form-select form-select-lg"&gt;...&lt;/select&gt;
&lt;select class="form-select form-select-sm"&gt;...&lt;/select&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Checks and Radios -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Checks and Radios</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
                                            <label class="form-check-label" for="flexCheckDefault">
                                                Default checkbox
                                            </label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" value="" id="flexCheckChecked" checked>
                                            <label class="form-check-label" for="flexCheckChecked">
                                                Checked checkbox
                                            </label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault1">
                                            <label class="form-check-label" for="flexRadioDefault1">
                                                Default radio
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault2" checked>
                                            <label class="form-check-label" for="flexRadioDefault2">
                                                Default checked radio
                                            </label>
                                        </div>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;div class="form-check"&gt;
  &lt;input class="form-check-input" type="checkbox" value="" id="flexCheckDefault"&gt;
  &lt;label class="form-check-label" for="flexCheckDefault"&gt;
    Default checkbox
  &lt;/label&gt;
&lt;/div&gt;
&lt;div class="form-check"&gt;
  &lt;input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault1"&gt;
  &lt;label class="form-check-label" for="flexRadioDefault1"&gt;
    Default radio
  &lt;/label&gt;
&lt;/div&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Switches -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Switches</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckDefault">
                                            <label class="form-check-label" for="flexSwitchCheckDefault">Default switch checkbox input</label>
                                        </div>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckChecked" checked>
                                            <label class="form-check-label" for="flexSwitchCheckChecked">Checked switch checkbox input</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckDisabled" disabled>
                                            <label class="form-check-label" for="flexSwitchCheckDisabled">Disabled switch checkbox input</label>
                                        </div>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;div class="form-check form-switch"&gt;
  &lt;input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckDefault"&gt;
  &lt;label class="form-check-label" for="flexSwitchCheckDefault"&gt;Default switch checkbox input&lt;/label&gt;
&lt;/div&gt;
&lt;div class="form-check form-switch"&gt;
  &lt;input class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckChecked" checked&gt;
  &lt;label class="form-check-label" for="flexSwitchCheckChecked"&gt;Checked switch checkbox input&lt;/label&gt;
&lt;/div&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Range -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Range</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <label for="customRange1" class="form-label">Example range</label>
                                        <input type="range" class="form-range mb-3" id="customRange1">
                                        <label for="customRange2" class="form-label">Example range</label>
                                        <input type="range" class="form-range mb-3" min="0" max="5" id="customRange2">
                                        <label for="customRange3" class="form-label">Example range</label>
                                        <input type="range" class="form-range" min="0" max="5" step="0.5" id="customRange3">
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;label for="customRange1" class="form-label"&gt;Example range&lt;/label&gt;
&lt;input type="range" class="form-range" id="customRange1"&gt;

&lt;label for="customRange2" class="form-label"&gt;Example range&lt;/label&gt;
&lt;input type="range" class="form-range" min="0" max="5" id="customRange2"&gt;

&lt;label for="customRange3" class="form-label"&gt;Example range&lt;/label&gt;
&lt;input type="range" class="form-range" min="0" max="5" step="0.5" id="customRange3"&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Input Groups -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Input Groups</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <div class="input-group mb-3">
                                            <span class="input-group-text" id="basic-addon1">@</span>
                                            <input type="text" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1">
                                        </div>
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control" placeholder="Recipient's username" aria-label="Recipient's username" aria-describedby="basic-addon2">
                                            <span class="input-group-text" id="basic-addon2">@example.com</span>
                                        </div>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text">₹</span>
                                            <input type="text" class="form-control" aria-label="Amount (to the nearest dollar)">
                                            <span class="input-group-text">.00</span>
                                        </div>
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Username" aria-label="Username">
                                            <span class="input-group-text">@</span>
                                            <input type="text" class="form-control" placeholder="Server" aria-label="Server">
                                        </div>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;div class="input-group mb-3"&gt;
  &lt;span class="input-group-text" id="basic-addon1"&gt;@&lt;/span&gt;
  &lt;input type="text" class="form-control" placeholder="Username" aria-label="Username" aria-describedby="basic-addon1"&gt;
&lt;/div&gt;

&lt;div class="input-group mb-3"&gt;
  &lt;input type="text" class="form-control" placeholder="Recipient's username" aria-label="Recipient's username" aria-describedby="basic-addon2"&gt;
  &lt;span class="input-group-text" id="basic-addon2"&gt;@example.com&lt;/span&gt;
&lt;/div&gt;

&lt;div class="input-group mb-3"&gt;
  &lt;span class="input-group-text"&gt;$&lt;/span&gt;
  &lt;input type="text" class="form-control" aria-label="Amount (to the nearest dollar)"&gt;
  &lt;span class="input-group-text"&gt;.00&lt;/span&gt;
&lt;/div&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Labels -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Floating Labels</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <div class="form-floating">
                                                    <input type="email" class="form-control" id="floatingInput" placeholder="name@example.com">
                                                    <label for="floatingInput">Email address</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-floating">
                                                    <input type="password" class="form-control" id="floatingPassword" placeholder="Password" autocomplete="new-password">
                                                    <label for="floatingPassword">Password</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-floating">
                                                    <select class="form-select" id="floatingSelect" aria-label="Floating label select example">
                                                        <option selected>Open this select menu</option>
                                                        <option value="1">One</option>
                                                        <option value="2">Two</option>
                                                        <option value="3">Three</option>
                                                    </select>
                                                    <label for="floatingSelect">Works with selects</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <textarea class="form-control" placeholder="Leave a comment here" id="floatingTextarea2" style="height: 100px"></textarea>
                                                    <label for="floatingTextarea2">Comments</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;div class="form-floating"&gt;
  &lt;input type="email" class="form-control" id="floatingInput" placeholder="name@example.com"&gt;
  &lt;label for="floatingInput"&gt;Email address&lt;/label&gt;
&lt;/div&gt;
&lt;div class="form-floating"&gt;
  &lt;input type="password" class="form-control" id="floatingPassword" placeholder="Password"&gt;
  &lt;label for="floatingPassword"&gt;Password&lt;/label&gt;
&lt;/div&gt;
&lt;div class="form-floating"&gt;
  &lt;select class="form-select" id="floatingSelect"&gt;
    &lt;option selected&gt;Open this select menu&lt;/option&gt;
    &lt;option value="1"&gt;One&lt;/option&gt;
  &lt;/select&gt;
  &lt;label for="floatingSelect"&gt;Works with selects&lt;/label&gt;
&lt;/div&gt;</code></pre>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode(this)">
                                        <i class="bi bi-clipboard me-2"></i>Copy Code
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Form Validation -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h2 class="h5 card-title mb-0">Form Validation</h2>
                                </div>
                                <div class="card-body">
                                    <div class="element-preview-container">
                                        <form class="row g-3 needs-validation" novalidate>
                                            <div class="col-md-4">
                                                <label for="validationCustom01" class="form-label">First name</label>
                                                <input type="text" class="form-control" id="validationCustom01" value="Mark" required>
                                                <div class="valid-feedback">
                                                    Looks good!
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="validationCustom02" class="form-label">Last name</label>
                                                <input type="text" class="form-control" id="validationCustom02" value="Otto" required>
                                                <div class="valid-feedback">
                                                    Looks good!
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="validationCustomUsername" class="form-label">Username</label>
                                                <div class="input-group has-validation">
                                                    <span class="input-group-text" id="inputGroupPrepend">@</span>
                                                    <input type="text" class="form-control" id="validationCustomUsername" aria-describedby="inputGroupPrepend" required>
                                                    <div class="invalid-feedback">
                                                        Please choose a username.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="validationCustom03" class="form-label">City</label>
                                                <input type="text" class="form-control" id="validationCustom03" required>
                                                <div class="invalid-feedback">
                                                    Please provide a valid city.
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="validationCustom04" class="form-label">State</label>
                                                <select class="form-select" id="validationCustom04" required>
                                                    <option selected disabled value="">Choose...</option>
                                                    <option>...</option>
                                                </select>
                                                <div class="invalid-feedback">
                                                    Please select a valid state.
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="validationCustom05" class="form-label">Zip</label>
                                                <input type="text" class="form-control" id="validationCustom05" required>
                                                <div class="invalid-feedback">
                                                    Please provide a valid zip.
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="" id="invalidCheck" required>
                                                    <label class="form-check-label" for="invalidCheck">
                                                        Agree to terms and conditions
                                                    </label>
                                                    <div class="invalid-feedback">
                                                        You must agree before submitting.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-primary" type="submit">Submit form</button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="element-code-block">
                                        <pre><code class="language-html">&lt;form class="row g-3 needs-validation" novalidate&gt;
  &lt;div class="col-md-4"&gt;
    &lt;label for="validationCustom01" class="form-label"&gt;First name&lt;/label&gt;
    &lt;input type="text" class="form-control" id="validationCustom01" value="Mark" required&gt;
    &lt;div class="valid-feedback"&gt;
      Looks good!
    &lt;/div&gt;
  &lt;/div&gt;
  &lt;div class="col-md-4"&gt;
    &lt;label for="validationCustom02" class="form-label"&gt;Last name&lt;/label&gt;
    &lt;input type="text" class="form-control" id="validationCustom02" value="Otto" required&gt;
    &lt;div class="valid-feedback"&gt;
      Looks good!
    &lt;/div&gt;
  &lt;/div&gt;
  &lt;div class="col-md-4"&gt;
    &lt;label for="validationCustomUsername" class="form-label"&gt;Username&lt;/label&gt;
    &lt;div class="input-group has-validation"&gt;
      &lt;span class="input-group-text" id="inputGroupPrepend"&gt;@&lt;/span&gt;
      &lt;input type="text" class="form-control" id="validationCustomUsername" aria-describedby="inputGroupPrepend" required&gt;
      &lt;div class="invalid-feedback"&gt;
        Please choose a username.
      &lt;/div&gt;
    &lt;/div&gt;
  &lt;/div&gt;
  &lt;div class="col-12"&gt;
    &lt;button class="btn btn-primary" type="submit"&gt;Submit form&lt;/button&gt;
  &lt;/div&gt;
&lt;/form&gt;</code></pre>
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

            // Form validation
            const forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });

            // Apply syntax highlighting
            initializeSyntaxHighlighting();
        });
    </script>
@endpush
