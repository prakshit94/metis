import re

with open('/home/user/metis/resources/views/dashboard.blade.php', 'r') as f:
    content = f.read()

pattern = r"fetch\(`/customers/search-by-phone\?phone=\$\{this\.searchPhone\}`\)"
replacement = """fetch(`/customers/search-by-phone?phone=${this.searchPhone}`, {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })"""

content = re.sub(pattern, replacement, content)

# Also handle the response if not ok
pattern2 = r"""                                    \.then\(res => res\.json\(\)\)
                                    \.then\(data => \{"""
replacement2 = """                                    .then(async res => {
                                        if (!res.ok) {
                                            if (res.status === 403) throw new Error('You do not have permission to search customers.');
                                            throw new Error('Error searching customer.');
                                        }
                                        return res.json();
                                    })
                                    .then(data => {"""

content = re.sub(pattern2, replacement2, content)

# And in the catch block:
pattern3 = r"""                                        this\.errorMsg = 'Error searching customer\. Please try again\.';"""
replacement3 = """                                        this.errorMsg = err.message || 'Error searching customer. Please try again.';"""

content = re.sub(pattern3, replacement3, content)

with open('/home/user/metis/resources/views/dashboard.blade.php', 'w') as f:
    f.write(content)
