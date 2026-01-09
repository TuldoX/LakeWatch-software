<script></script>
<template>
    <div id="device-name" class="container-surface-no-style">
      <img src="../../assets/icons/drop.png" id="drop">
      <div class="helper">
        <h2 id="device-name-text">Axiom V1</h2>
        <div id="status">
          <p id="status-text">online</p>
        </div>
      </div>
    </div>
    <div class="divider"></div>
</template>
<style scoped>
    #drop{
    width: 4.75rem;
    height: 4.75rem;
  }

  #device-name-text {
    margin: 0;
    padding: 0;
    font-size: clamp(1.25rem, 1.25vw, 1.5rem);
    font-weight: 500;
  }

  #status {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 4.875rem;
    height: 1.438rem;
    border-radius: 10px;
    background: linear-gradient(to right, #1976D2 0%, #42A5F5 100%);
  }

  #status-text{
    color: white;
  }

  .helper {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.3rem;
  }

  .divider {
    width: 100vw;
    height: 1px;
    background-color: var(--border);
    margin-left: -1rem;
    margin-top: 0.4rem;
    margin-bottom: 1rem;
  }
</style>